<?php

use App\Models\MovieAsset;
use App\Services\VideoIngestService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('streaming:repair-source {--movie_id=} {--limit=50} {--dry-run}', function () {
    $movieId = (int) $this->option('movie_id');
    $limit = max(1, (int) $this->option('limit'));
    $dryRun = (bool) $this->option('dry-run');
    $disk = (string) config('filesystems.default', 'local');

    $query = MovieAsset::query()
        ->with('movie')
        ->whereNotNull('download_file_path')
        ->where(function ($builder): void {
            $builder
                ->whereNull('hls_master_path')
                ->orWhereRaw('LOWER(hls_master_path) NOT LIKE ?', ['%.m3u8']);
        });

    if ($movieId > 0) {
        $query->where('movie_id', $movieId);
    }

    $assets = $query->limit($limit)->get();

    if ($assets->isEmpty()) {
        $this->info('No source-only movies found for repair.');
        return;
    }

    /** @var VideoIngestService $ingest */
    $ingest = app(VideoIngestService::class);
    $ok = 0;
    $failed = 0;
    $skipped = 0;

    foreach ($assets as $asset) {
        $movie = $asset->movie;
        $title = $movie?->title ?? "movie_id={$asset->movie_id}";
        $sourcePath = (string) ($asset->download_file_path ?? '');

        if (! $movie) {
            $this->warn("Skipping movie_id={$asset->movie_id}: movie record missing.");
            $skipped++;
            continue;
        }

        if ($sourcePath === '') {
            $this->warn("Skipping {$title}: no download/source path.");
            $skipped++;
            continue;
        }

        if (! Storage::disk($disk)->exists($sourcePath)) {
            $this->warn("Skipping {$title}: source file missing on disk ({$sourcePath}).");
            $skipped++;
            continue;
        }

        $rawRenditions = is_array($asset->renditions_json) ? $asset->renditions_json : [];
        $requestedRenditions = array_values(array_filter(array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            $rawRenditions
        ), static fn (string $value): bool => preg_match('/^\d{3,4}p$/', $value) === 1));

        $this->line("Processing {$title} ({$sourcePath})...");

        if ($dryRun) {
            $this->line('  dry-run: skipped write');
            $skipped++;
            continue;
        }

        try {
            $payload = $ingest->ingestFromStoredSource(
                $movie,
                $sourcePath,
                $requestedRenditions,
                false
            );

            $asset->fill($payload);
            $asset->save();

            $this->info("  ok -> {$payload['hls_master_path']}");
            $ok++;
        } catch (\Throwable $exception) {
            $this->error('  failed: '.trim($exception->getMessage()));
            $failed++;
        }
    }

    $this->newLine();
    $this->info("Done. success={$ok}, failed={$failed}, skipped={$skipped}");
})->purpose('Repair source-only movie assets into adaptive HLS variants');
