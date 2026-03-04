<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Movie;
use App\Models\Vj;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class MovieController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:draft,published'],
            'type' => ['nullable', 'in:movie,series'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'vj_id' => ['nullable', 'integer', 'exists:vjs,id'],
            'sort' => ['nullable', 'in:newest,oldest,title'],
        ]);

        $filters = [
            'search' => $this->cleanString($filters['search'] ?? null),
            'status' => $this->cleanString($filters['status'] ?? null),
            'type' => $this->cleanString($filters['type'] ?? null),
            'language_id' => $filters['language_id'] ?? null,
            'vj_id' => $filters['vj_id'] ?? null,
            'sort' => $this->cleanString($filters['sort'] ?? null) ?: 'newest',
        ];

        $query = Movie::query()
            ->with(['language', 'vj', 'genres'])
            ->when($filters['search'], function ($builder, string $search): void {
                $operator = $this->searchOperator();
                $term = '%'.$search.'%';

                $builder->where(function ($nested) use ($operator, $term): void {
                    $nested
                        ->where('title', $operator, $term)
                        ->orWhere('series_title', $operator, $term)
                        ->orWhere('slug', $operator, $term);
                });
            })
            ->when($filters['status'], fn ($builder, string $status) => $builder->where('status', $status))
            ->when($filters['type'], fn ($builder, string $type) => $builder->where('content_type', $type))
            ->when($filters['language_id'], fn ($builder, $languageId) => $builder->where('language_id', (int) $languageId))
            ->when($filters['vj_id'], fn ($builder, $vjId) => $builder->where('vj_id', (int) $vjId));

        match ($filters['sort']) {
            'oldest' => $query->oldest(),
            'title' => $query->orderBy('title'),
            default => $query->latest(),
        };

        $movies = $query->paginate(20)->withQueryString();

        return view('admin.movies.index', [
            'movies' => $movies,
            'filters' => $filters,
            'languages' => Language::query()->orderBy('name')->get(['id', 'name']),
            'vjs' => Vj::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('admin.movies.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $movie = new Movie;

        $validated = $this->validateRequest($request, $movie);
        $validated['created_by'] = $request->user()->id;

        DB::transaction(function () use ($movie, $request, $validated): void {
            $movie->fill(Arr::except($validated, ['genre_ids', 'hls_master_path', 'preview_clip_path', 'download_file_path', 'renditions_json', 'size_bytes']));
            $movie->save();

            $movie->genres()->sync($validated['genre_ids'] ?? []);
            $movie->asset()->updateOrCreate([], $this->assetPayload($request, $movie, $validated));
        });

        return redirect()->route('admin.movies.index')->with('status', 'Movie created.');
    }

    public function edit(Movie $movie): View
    {
        $movie->load(['genres', 'asset']);

        return view('admin.movies.edit', $this->formData() + compact('movie'));
    }

    public function update(Request $request, Movie $movie): RedirectResponse
    {
        $validated = $this->validateRequest($request, $movie);

        DB::transaction(function () use ($movie, $request, $validated): void {
            $movie->update(Arr::except($validated, ['genre_ids', 'hls_master_path', 'preview_clip_path', 'download_file_path', 'renditions_json', 'size_bytes']));
            $movie->genres()->sync($validated['genre_ids'] ?? []);
            $movie->asset()->updateOrCreate([], $this->assetPayload($request, $movie, $validated));
        });

        return redirect()->route('admin.movies.index')->with('status', 'Movie updated.');
    }

    public function destroy(Movie $movie): RedirectResponse
    {
        $movie->delete();

        return redirect()->route('admin.movies.index')->with('status', 'Movie deleted.');
    }

    private function formData(): array
    {
        $hlsMasterMaxKb = $this->maxUploadKb(51200);
        $previewMaxKb = $this->maxUploadKb(512000);
        $downloadMaxKb = $this->maxUploadKb(1572864);
        $hlsPackageMaxKb = $this->maxUploadKb(1024000);
        $defaultDisk = (string) config('filesystems.default', 'local');
        $defaultDiskDriver = (string) config("filesystems.disks.{$defaultDisk}.driver", 'local');
        $publicStoragePath = public_path('storage');

        return [
            'languages' => Language::query()->orderBy('name')->get(),
            'vjs' => Vj::query()->orderBy('name')->get(),
            'genres' => Genre::query()->orderBy('name')->get(),
            'uploadLimits' => [
                'hls_master' => $hlsMasterMaxKb,
                'hls_package' => $hlsPackageMaxKb,
                'preview' => $previewMaxKb,
                'download' => $downloadMaxKb,
                'server_upload' => $this->toKb((string) ini_get('upload_max_filesize')),
                'server_post' => $this->toKb((string) ini_get('post_max_size')),
            ],
            'diskInfo' => [
                'name' => $defaultDisk,
                'driver' => $defaultDiskDriver,
                'supports_hls_package' => $defaultDiskDriver === 'local',
                'public_storage_linked' => is_link($publicStoragePath) || is_dir($publicStoragePath),
            ],
        ];
    }

    private function validateRequest(Request $request, Movie $movie): array
    {
        $hlsMasterMaxKb = $this->maxUploadKb(51200); // 50 MB
        $previewMaxKb = $this->maxUploadKb(512000); // 500 MB
        $downloadMaxKb = $this->maxUploadKb(1572864); // 1.5 GB
        $hlsPackageMaxKb = $this->maxUploadKb(1024000); // 1 GB

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('movies', 'slug')->ignore($movie->id)],
            'content_type' => ['required', 'in:movie,series'],
            'series_title' => ['nullable', 'string', 'max:255'],
            'season_number' => ['nullable', 'integer', 'min:1'],
            'episode_number' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'poster_url' => ['nullable', 'string', 'max:2048'],
            'backdrop_url' => ['nullable', 'string', 'max:2048'],
            'poster_file' => ['nullable', 'image', 'max:5120'],
            'backdrop_file' => ['nullable', 'image', 'max:8192'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'duration_seconds' => ['required', 'integer', 'min:1'],
            'age_rating' => ['nullable', 'string', 'max:20'],
            'language_id' => ['required', 'exists:languages,id'],
            'vj_id' => ['required', 'exists:vjs,id'],
            'is_featured' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'genre_ids' => ['nullable', 'array'],
            'genre_ids.*' => ['exists:genres,id'],
            'hls_master_path' => ['nullable', 'string', 'max:2048'],
            'hls_master_upload' => ['nullable', 'file', 'extensions:m3u8', 'max:'.$hlsMasterMaxKb],
            'hls_package_upload' => ['nullable', 'file', 'mimes:zip', 'max:'.$hlsPackageMaxKb],
            'preview_clip_path' => ['nullable', 'string', 'max:2048'],
            'preview_clip_upload' => ['nullable', 'file', 'max:'.$previewMaxKb],
            'download_file_path' => ['nullable', 'string', 'max:2048'],
            'download_file_upload' => ['nullable', 'file', 'max:'.$downloadMaxKb],
            'renditions_json' => ['nullable', 'string', 'max:5000'],
            'size_bytes' => ['nullable', 'integer', 'min:0'],
        ], [
            'hls_master_upload.extensions' => 'HLS master upload must be a .m3u8 playlist file.',
            'hls_master_upload.max' => 'HLS master upload is larger than allowed by current server limits.',
            'hls_package_upload.max' => 'HLS package upload is larger than allowed by current server limits.',
            'preview_clip_upload.max' => 'Preview upload is larger than allowed by current server limits.',
            'download_file_upload.max' => 'Download upload is larger than allowed by current server limits.',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);
        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
        $validated['content_type'] = $validated['content_type'] ?? 'movie';
        $validated['series_title'] = $validated['content_type'] === 'series'
            ? ($validated['series_title'] ?: $validated['title'])
            : null;
        $validated['season_number'] = $validated['content_type'] === 'series'
            ? ($validated['season_number'] ?? 1)
            : null;
        $validated['episode_number'] = $validated['content_type'] === 'series'
            ? ($validated['episode_number'] ?? 1)
            : null;
        $validated['renditions_json'] = $validated['renditions_json']
            ? array_values(array_filter(array_map('trim', explode(',', $validated['renditions_json']))))
            : [];
        $validated['poster_url'] = $this->cleanString($validated['poster_url'] ?? null);
        $validated['backdrop_url'] = $this->cleanString($validated['backdrop_url'] ?? null);
        $validated['hls_master_path'] = $this->cleanString($validated['hls_master_path'] ?? null);
        $validated['preview_clip_path'] = $this->cleanString($validated['preview_clip_path'] ?? null);
        $validated['download_file_path'] = $this->cleanString($validated['download_file_path'] ?? null);

        $hasUploadedMaster = $request->file('hls_master_upload') instanceof UploadedFile;
        $hasUploadedPackage = $request->file('hls_package_upload') instanceof UploadedFile;
        $hasExistingMaster = ! empty($movie->asset?->hls_master_path);
        if (empty($validated['hls_master_path']) && ! $hasUploadedMaster && ! $hasUploadedPackage && ! $hasExistingMaster) {
            throw ValidationException::withMessages([
                'hls_master_path' => 'Provide HLS master path or upload an HLS .m3u8 file / ZIP package.',
            ]);
        }

        if ($request->file('poster_file') instanceof UploadedFile) {
            $validated['poster_url'] = Storage::disk('public')->url(
                $request->file('poster_file')->store('movies/posters', 'public')
            );
        }

        if ($request->file('backdrop_file') instanceof UploadedFile) {
            $validated['backdrop_url'] = Storage::disk('public')->url(
                $request->file('backdrop_file')->store('movies/backdrops', 'public')
            );
        }

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        return $validated;
    }

    private function assetPayload(Request $request, Movie $movie, array $validated): array
    {
        $payload = Arr::only($validated, [
            'hls_master_path',
            'preview_clip_path',
            'download_file_path',
            'renditions_json',
            'size_bytes',
        ]);

        if ($request->file('hls_package_upload') instanceof UploadedFile) {
            $payload['hls_master_path'] = $this->storeHlsPackageUpload($request->file('hls_package_upload'), $movie);
        } elseif ($request->file('hls_master_upload') instanceof UploadedFile) {
            $payload['hls_master_path'] = $request->file('hls_master_upload')->store("movies/streams/{$movie->id}", config('filesystems.default'));
        }

        if ($request->file('preview_clip_upload') instanceof UploadedFile) {
            $payload['preview_clip_path'] = Storage::disk('public')->url(
                $request->file('preview_clip_upload')->store("movies/previews/{$movie->id}", 'public')
            );
        }

        if ($request->file('download_file_upload') instanceof UploadedFile) {
            $payload['download_file_path'] = $request->file('download_file_upload')->store("movies/downloads/{$movie->id}", config('filesystems.default'));
            $payload['size_bytes'] = $request->file('download_file_upload')->getSize();
        }

        $existingHlsPath = $movie->asset?->hls_master_path;
        if (empty($payload['hls_master_path']) && empty($existingHlsPath)) {
            throw ValidationException::withMessages([
                'hls_master_path' => 'Provide HLS master path or upload a master playlist file.',
            ]);
        }

        if (empty($payload['hls_master_path']) && ! empty($existingHlsPath)) {
            $payload['hls_master_path'] = $existingHlsPath;
        }

        return $payload;
    }

    private function storeHlsPackageUpload(UploadedFile $file, Movie $movie): string
    {
        $diskName = (string) config('filesystems.default', 'local');
        $diskDriver = (string) config("filesystems.disks.{$diskName}.driver", 'local');

        if ($diskDriver !== 'local') {
            throw ValidationException::withMessages([
                'hls_package_upload' => 'HLS package extraction is supported only when FILESYSTEM_DISK uses a local driver.',
            ]);
        }

        $disk = Storage::disk($diskName);
        $basePath = "movies/streams/{$movie->id}/package-".now()->format('YmdHis');
        $zipPath = $file->storeAs($basePath, 'hls-package.zip', $diskName);
        $zipAbsolutePath = $disk->path($zipPath);
        $extractRelativePath = "{$basePath}/extracted";
        $extractAbsolutePath = $disk->path($extractRelativePath);

        File::ensureDirectoryExists($extractAbsolutePath);

        $zip = new ZipArchive;
        $openResult = $zip->open($zipAbsolutePath);

        if ($openResult !== true) {
            throw ValidationException::withMessages([
                'hls_package_upload' => 'Uploaded HLS package could not be opened as a ZIP archive.',
            ]);
        }

        $extracted = $zip->extractTo($extractAbsolutePath);
        $zip->close();

        if (! $extracted) {
            throw ValidationException::withMessages([
                'hls_package_upload' => 'Uploaded HLS package could not be extracted.',
            ]);
        }

        $disk->delete($zipPath);

        $masterPlaylistPath = $this->findMasterPlaylistPath($extractAbsolutePath);

        if (! $masterPlaylistPath) {
            throw ValidationException::withMessages([
                'hls_package_upload' => 'No .m3u8 playlist was found in the uploaded HLS package.',
            ]);
        }

        return $this->toDiskRelativePath($diskName, $masterPlaylistPath);
    }

    private function findMasterPlaylistPath(string $extractAbsolutePath): ?string
    {
        $playlistFiles = collect(File::allFiles($extractAbsolutePath))
            ->filter(fn ($file) => strtolower((string) $file->getExtension()) === 'm3u8')
            ->values();

        if ($playlistFiles->isEmpty()) {
            return null;
        }

        $preferred = $playlistFiles->first(
            fn ($file) => in_array(strtolower((string) $file->getFilename()), ['master.m3u8', 'index.m3u8'], true)
        );

        return ($preferred ?? $playlistFiles->first())?->getPathname();
    }

    private function toDiskRelativePath(string $diskName, string $absolutePath): string
    {
        $absolutePath = str_replace('\\', '/', $absolutePath);
        $rootPath = str_replace('\\', '/', rtrim(Storage::disk($diskName)->path(''), '/\\'));

        if (! str_starts_with($absolutePath, $rootPath.'/')) {
            throw ValidationException::withMessages([
                'hls_package_upload' => 'Failed to map uploaded HLS package paths to the configured storage disk.',
            ]);
        }

        return ltrim(substr($absolutePath, strlen($rootPath)), '/');
    }

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim($value);

        return $cleaned !== '' ? $cleaned : null;
    }

    private function searchOperator(): string
    {
        return Movie::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    private function maxUploadKb(int $configuredMaxKb): int
    {
        $uploadLimitKb = $this->toKb((string) ini_get('upload_max_filesize'));
        $postLimitKb = $this->toKb((string) ini_get('post_max_size'));
        $serverLimitKb = min(array_filter([$uploadLimitKb, $postLimitKb])) ?: 0;

        if ($serverLimitKb <= 0) {
            return $configuredMaxKb;
        }

        return max(1, min($configuredMaxKb, $serverLimitKb));
    }

    private function toKb(string $value): int
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return 0;
        }

        $unit = strtolower(substr($trimmed, -1));
        $number = (float) $trimmed;

        return match ($unit) {
            'g' => (int) round($number * 1024 * 1024),
            'm' => (int) round($number * 1024),
            'k' => (int) round($number),
            default => (int) round($number / 1024),
        };
    }
}
