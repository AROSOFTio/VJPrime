<?php

namespace App\Services;

use App\Models\Movie;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class VideoIngestService
{
    /**
     * Ingest a single source video and generate streaming assets automatically.
     *
     * @return array{hls_master_path:string, preview_clip_path:string, download_file_path:string, renditions_json:array<int, string>, size_bytes:int}
     */
    public function ingest(Movie $movie, UploadedFile $sourceVideo, array $requestedRenditions = []): array
    {
        if (! (bool) config('streaming.autoprocess.enabled', true)) {
            throw ValidationException::withMessages([
                'source_video_upload' => 'Automatic video processing is disabled on this server.',
            ]);
        }

        $diskName = (string) config('filesystems.default', 'local');
        $diskDriver = (string) config("filesystems.disks.{$diskName}.driver", 'local');

        if ($diskDriver !== 'local') {
            throw ValidationException::withMessages([
                'source_video_upload' => 'Automatic video processing requires FILESYSTEM_DISK to use a local driver.',
            ]);
        }

        $this->assertBinaryAvailable($this->ffmpegBinary(), 'FFmpeg');
        $this->assertBinaryAvailable($this->ffprobeBinary(), 'FFprobe');

        $timestamp = now()->format('YmdHis');
        $extension = strtolower($sourceVideo->getClientOriginalExtension() ?: $sourceVideo->extension() ?: 'mp4');

        $downloadRelativePath = $sourceVideo->storeAs(
            "movies/downloads/{$movie->id}",
            "source-{$timestamp}.{$extension}",
            $diskName
        );

        if (! is_string($downloadRelativePath) || $downloadRelativePath === '') {
            throw ValidationException::withMessages([
                'source_video_upload' => 'Failed to store uploaded source video.',
            ]);
        }

        $downloadAbsolutePath = Storage::disk($diskName)->path($downloadRelativePath);
        $videoMeta = $this->probeVideo($downloadAbsolutePath);

        $hlsRelativeDir = "movies/streams/{$movie->id}/auto-{$timestamp}/hls";
        $hlsAbsoluteDir = Storage::disk($diskName)->path($hlsRelativeDir);
        File::ensureDirectoryExists($hlsAbsoluteDir);

        $renditions = $this->resolveRenditions($requestedRenditions, (int) ($videoMeta['height'] ?? 1080));
        $this->generateHls($downloadAbsolutePath, $hlsAbsoluteDir, $renditions, (bool) ($videoMeta['has_audio'] ?? true));

        $previewRelativePath = "movies/previews/{$movie->id}/auto-{$timestamp}.mp4";
        $previewAbsolutePath = Storage::disk('public')->path($previewRelativePath);
        File::ensureDirectoryExists(dirname($previewAbsolutePath));

        $duration = (float) ($videoMeta['duration'] ?? 0);
        $previewSeconds = max(8, (int) config('streaming.autoprocess.preview_seconds', 20));
        $startSecond = $duration > ($previewSeconds + 10)
            ? (int) floor(max(0, $duration * 0.2))
            : 0;

        $this->generatePreviewClip($downloadAbsolutePath, $previewAbsolutePath, $startSecond, $previewSeconds);

        return [
            'hls_master_path' => "{$hlsRelativeDir}/master.m3u8",
            'preview_clip_path' => Storage::disk('public')->url($previewRelativePath),
            'download_file_path' => $downloadRelativePath,
            'renditions_json' => array_merge(['auto'], array_map(fn (int $h) => "{$h}p", $renditions)),
            'size_bytes' => (int) ($sourceVideo->getSize() ?? 0),
        ];
    }

    private function generateHls(string $sourcePath, string $outputDir, array $renditions, bool $hasAudio): void
    {
        if (empty($renditions)) {
            throw ValidationException::withMessages([
                'source_video_upload' => 'No valid renditions were selected for HLS generation.',
            ]);
        }

        foreach (array_keys($renditions) as $index) {
            File::ensureDirectoryExists($outputDir.'/v'.$index);
        }

        $command = [
            $this->ffmpegBinary(),
            '-y',
            '-i', $sourcePath,
            '-preset', (string) config('streaming.autoprocess.ffmpeg_preset', 'veryfast'),
            '-g', '48',
            '-sc_threshold', '0',
        ];

        $varStreamEntries = [];

        foreach (array_values($renditions) as $index => $height) {
            $bitrate = $this->bitrateForHeight($height);
            $maxrate = (int) round($bitrate * 1.07);
            $bufsize = (int) round($bitrate * 1.5);

            $command[] = '-map';
            $command[] = '0:v:0';
            if ($hasAudio) {
                $command[] = '-map';
                $command[] = '0:a:0?';
            }

            $command[] = "-c:v:{$index}";
            $command[] = 'libx264';
            $command[] = "-profile:v:{$index}";
            $command[] = 'main';
            $command[] = "-crf:{$index}";
            $command[] = '21';
            $command[] = "-b:v:{$index}";
            $command[] = "{$bitrate}k";
            $command[] = "-maxrate:v:{$index}";
            $command[] = "{$maxrate}k";
            $command[] = "-bufsize:v:{$index}";
            $command[] = "{$bufsize}k";
            $command[] = "-vf:v:{$index}";
            $command[] = "scale=-2:{$height}";

            if ($hasAudio) {
                $command[] = "-c:a:{$index}";
                $command[] = 'aac';
                $command[] = "-b:a:{$index}";
                $command[] = '128k';
                $varStreamEntries[] = "v:{$index},a:{$index}";
            } else {
                $varStreamEntries[] = "v:{$index}";
            }
        }

        $segmentDuration = max(2, (int) config('streaming.autoprocess.hls_segment_seconds', 6));
        $command = array_merge($command, [
            '-f', 'hls',
            '-hls_time', (string) $segmentDuration,
            '-hls_playlist_type', 'vod',
            '-hls_flags', 'independent_segments',
            '-hls_segment_filename', $outputDir.'/v%v/segment_%05d.ts',
            '-master_pl_name', 'master.m3u8',
            '-var_stream_map', implode(' ', $varStreamEntries),
            $outputDir.'/v%v/index.m3u8',
        ]);

        $this->runProcess($command, 'source_video_upload');
    }

    private function generatePreviewClip(string $sourcePath, string $previewPath, int $startSecond, int $lengthSeconds): void
    {
        $this->runProcess([
            $this->ffmpegBinary(),
            '-y',
            '-ss', (string) $startSecond,
            '-i', $sourcePath,
            '-t', (string) $lengthSeconds,
            '-an',
            '-vf', "scale='min(960,iw)':-2",
            '-c:v', 'libx264',
            '-preset', (string) config('streaming.autoprocess.ffmpeg_preset', 'veryfast'),
            '-crf', '24',
            '-movflags', '+faststart',
            $previewPath,
        ], 'preview_clip_upload');
    }

    private function probeVideo(string $sourcePath): array
    {
        $output = $this->runProcess([
            $this->ffprobeBinary(),
            '-v', 'error',
            '-show_entries', 'stream=index,codec_type,width,height:format=duration',
            '-of', 'json',
            $sourcePath,
        ], 'source_video_upload');

        $decoded = json_decode($output, true);
        $streams = is_array($decoded['streams'] ?? null) ? $decoded['streams'] : [];
        $format = is_array($decoded['format'] ?? null) ? $decoded['format'] : [];

        $video = collect($streams)->first(fn ($stream) => ($stream['codec_type'] ?? null) === 'video');
        $hasAudio = collect($streams)->contains(fn ($stream) => ($stream['codec_type'] ?? null) === 'audio');

        return [
            'width' => (int) ($video['width'] ?? 0),
            'height' => (int) ($video['height'] ?? 0),
            'duration' => (float) ($format['duration'] ?? 0),
            'has_audio' => (bool) $hasAudio,
        ];
    }

    private function resolveRenditions(array $requested, int $sourceHeight): array
    {
        $requestedHeights = collect($requested)
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter(fn (string $value) => preg_match('/^\d{3,4}p$/', $value) === 1)
            ->map(fn (string $value) => (int) rtrim($value, 'p'))
            ->filter(fn (int $height) => $height >= 240 && $height <= 2160)
            ->unique()
            ->sort()
            ->values();

        if ($requestedHeights->isEmpty()) {
            $requestedHeights = collect((array) config('streaming.autoprocess.default_heights', [360, 480, 720, 1080]))
                ->map(fn ($height) => (int) $height)
                ->filter(fn (int $height) => $height >= 240 && $height <= 2160)
                ->unique()
                ->sort()
                ->values();
        }

        $allowed = $requestedHeights
            ->filter(fn (int $height) => $sourceHeight <= 0 || $height <= $sourceHeight)
            ->values();

        if ($allowed->isEmpty() && $sourceHeight > 0) {
            $allowed = collect([min($sourceHeight, 1080)]);
        }

        return $allowed->all();
    }

    private function bitrateForHeight(int $height): int
    {
        return match (true) {
            $height <= 360 => 800,
            $height <= 480 => 1300,
            $height <= 720 => 2800,
            $height <= 1080 => 5000,
            default => 8000,
        };
    }

    private function ffmpegBinary(): string
    {
        return (string) config('streaming.autoprocess.ffmpeg_binary', 'ffmpeg');
    }

    private function ffprobeBinary(): string
    {
        return (string) config('streaming.autoprocess.ffprobe_binary', 'ffprobe');
    }

    private function assertBinaryAvailable(string $binary, string $label): void
    {
        $process = new Process([$binary, '-version']);
        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            throw ValidationException::withMessages([
                'source_video_upload' => "{$label} is not installed or not available in PATH on this server.",
            ]);
        }
    }

    private function runProcess(array $command, string $errorField): string
    {
        $process = new Process($command);
        $process->setTimeout((float) config('streaming.autoprocess.timeout_seconds', 0) ?: null);
        $process->run();

        if (! $process->isSuccessful()) {
            $error = trim($process->getErrorOutput() ?: $process->getOutput());
            $error = $error !== '' ? $error : 'Unknown FFmpeg processing error.';

            throw ValidationException::withMessages([
                $errorField => $error,
            ]);
        }

        return (string) $process->getOutput();
    }
}
