<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\User;
use App\Services\DeviceFingerprintService;
use App\Services\DownloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class StreamController extends Controller
{
    public function __construct(
        private readonly DownloadService $downloadService,
        private readonly DeviceFingerprintService $deviceFingerprintService
    ) {}

    public function playlist(Request $request, Movie $movie): RedirectResponse|Response
    {
        abort_unless($request->hasValidSignature(), 401);

        $path = $movie->asset?->hls_master_path;
        abort_unless($path, 404, 'No stream asset found.');

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return redirect()->away($path);
        }

        return $this->serveStreamAsset($request, $movie, $path);
    }

    public function source(Request $request, Movie $movie): RedirectResponse|Response
    {
        abort_unless($request->hasValidSignature(), 401);

        $path = $movie->asset?->download_file_path ?: $movie->asset?->hls_master_path;
        abort_unless($path, 404, 'No source stream found.');

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return redirect()->away($path);
        }

        return $this->serveStreamAsset($request, $movie, $path);
    }

    public function asset(Request $request, Movie $movie, string $encodedPath): Response
    {
        abort_unless($request->hasValidSignature(), 401);

        $path = $this->decodePath($encodedPath);
        abort_unless($path, 404, 'Invalid stream asset path.');

        $masterPath = $movie->asset?->hls_master_path;
        abort_unless($masterPath && ! filter_var($masterPath, FILTER_VALIDATE_URL), 404, 'No local stream configured.');

        $allowedRoot = $this->normalizePath(dirname($masterPath));
        if ($allowedRoot !== '' && ! str_starts_with($path.'/', $allowedRoot.'/')) {
            abort(403, 'Forbidden stream path.');
        }

        return $this->serveStreamAsset($request, $movie, $path);
    }

    public function download(Request $request, Movie $movie): Response|RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 401);

        $user = User::query()->findOrFail($request->integer('user'));
        $path = $movie->asset?->download_file_path;
        abort_unless($path, 404, 'No download file configured.');

        $this->downloadService->trackDownload(
            $user,
            $movie,
            $path,
            $this->deviceFingerprintService->fromRequest($request),
            $request->ip()
        );

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return redirect()->away($path);
        }

        $disk = config('filesystems.default', 'local');
        abort_unless(Storage::disk($disk)->exists($path), 404, 'File not found.');

        return Storage::disk($disk)->download($path, basename($path));
    }

    private function serveStreamAsset(Request $request, Movie $movie, string $path): Response
    {
        $disk = (string) config('filesystems.default', 'local');
        abort_unless(Storage::disk($disk)->exists($path), 404, 'Stream file not found.');

        if ($this->isPlaylist($path)) {
            $contents = Storage::disk($disk)->get($path);
            $contents = $this->rewritePlaylistContent($request, $movie, $path, $contents);

            return response($contents, 200, [
                'Content-Type' => $this->contentTypeFor($disk, $path),
                'Cache-Control' => 'private, max-age=60',
            ]);
        }

        if ($this->supportsLocalRangeStreaming($disk)) {
            return $this->streamLocalFileWithRange($request, Storage::disk($disk)->path($path), $this->contentTypeFor($disk, $path));
        }

        $readStream = Storage::disk($disk)->readStream($path);
        abort_unless(is_resource($readStream), 404, 'Stream file unreadable.');

        return response()->stream(function () use ($readStream): void {
            while (! feof($readStream)) {
                $chunk = fread($readStream, 1024 * 64);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                echo $chunk;
                if (function_exists('flush')) {
                    flush();
                }
            }
            fclose($readStream);
        }, 200, [
            'Content-Type' => $this->contentTypeFor($disk, $path),
            'Cache-Control' => 'private, max-age=300',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    private function supportsLocalRangeStreaming(string $disk): bool
    {
        return (string) config("filesystems.disks.{$disk}.driver", 'local') === 'local';
    }

    private function streamLocalFileWithRange(Request $request, string $absolutePath, string $contentType): Response
    {
        $size = filesize($absolutePath);
        if ($size === false || $size <= 0) {
            abort(404, 'Stream file unreadable.');
        }

        $start = 0;
        $end = $size - 1;
        $status = 200;
        $headers = [
            'Content-Type' => $contentType,
            'Cache-Control' => 'private, max-age=300',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => (string) $size,
        ];

        $range = $request->header('Range');
        if (is_string($range) && preg_match('/bytes=(\d*)-(\d*)/i', $range, $matches) === 1) {
            $rangeStart = $matches[1] !== '' ? (int) $matches[1] : $start;
            $rangeEnd = $matches[2] !== '' ? (int) $matches[2] : $end;

            if ($rangeStart > $rangeEnd || $rangeStart > $end) {
                return response('', 416, [
                    'Content-Range' => "bytes */{$size}",
                    'Accept-Ranges' => 'bytes',
                ]);
            }

            $start = max(0, $rangeStart);
            $end = min($end, $rangeEnd);
            $length = ($end - $start) + 1;

            $status = 206;
            $headers['Content-Length'] = (string) $length;
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        return response()->stream(function () use ($absolutePath, $start, $end): void {
            $handle = fopen($absolutePath, 'rb');
            if (! is_resource($handle)) {
                return;
            }

            if ($start > 0) {
                fseek($handle, $start);
            }

            $remaining = ($end - $start) + 1;
            while ($remaining > 0 && ! feof($handle)) {
                $readLength = min(1024 * 64, $remaining);
                $buffer = fread($handle, $readLength);
                if ($buffer === false || $buffer === '') {
                    break;
                }
                echo $buffer;
                $remaining -= strlen($buffer);
                if (function_exists('flush')) {
                    flush();
                }
            }

            fclose($handle);
        }, $status, $headers);
    }

    private function rewritePlaylistContent(Request $request, Movie $movie, string $playlistPath, string $contents): string
    {
        $playlistDirectory = $this->normalizePath(dirname($playlistPath));
        $expiresAt = now()->addMinutes((int) config('streaming.signed_playlist_minutes', 10));

        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];

        $rewritten = array_map(function (string $line) use ($request, $movie, $playlistDirectory, $expiresAt) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                return $line;
            }

            if (str_starts_with($trimmed, '#')) {
                if (! str_contains($trimmed, 'URI=')) {
                    return $line;
                }

                return (string) preg_replace_callback('/URI="([^"]+)"/', function (array $matches) use ($request, $movie, $playlistDirectory, $expiresAt) {
                    $rewrittenUri = $this->toSignedStreamUri($request, $movie, $playlistDirectory, $matches[1], $expiresAt);

                    return 'URI="'.($rewrittenUri ?? $matches[1]).'"';
                }, $line);
            }

            return $this->toSignedStreamUri($request, $movie, $playlistDirectory, $trimmed, $expiresAt) ?? $line;
        }, $lines);

        return implode("\n", $rewritten);
    }

    private function toSignedStreamUri(Request $request, Movie $movie, string $baseDirectory, string $uri, \DateTimeInterface $expiresAt): ?string
    {
        if ($this->isExternalUri($uri)) {
            return $uri;
        }

        $parts = parse_url($uri);
        if ($parts === false) {
            return null;
        }

        $path = $parts['path'] ?? '';
        if ($path === '' || str_starts_with($path, '/')) {
            return null;
        }

        $resolvedPath = $this->normalizePath(($baseDirectory !== '' ? $baseDirectory.'/' : '').$path);
        if ($resolvedPath === '') {
            return null;
        }

        $params = [
            'movie' => $movie->id,
            'encodedPath' => $this->encodePath($resolvedPath),
        ];

        if ($request->has('user')) {
            $params['user'] = $request->query('user');
        }

        if ($request->has('device')) {
            $params['device'] = $request->query('device');
        }

        return URL::temporarySignedRoute('stream.asset', $expiresAt, $params);
    }

    private function isExternalUri(string $uri): bool
    {
        return (bool) filter_var($uri, FILTER_VALIDATE_URL) || str_starts_with($uri, 'data:');
    }

    private function isPlaylist(string $path): bool
    {
        return str_ends_with(strtolower($path), '.m3u8');
    }

    private function contentTypeFor(string $disk, string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'm3u8' => 'application/vnd.apple.mpegurl',
            'ts' => 'video/mp2t',
            'm4s' => 'video/iso.segment',
            default => Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream',
        };
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '' || $path === '.' || $path === '/') {
            return '';
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function encodePath(string $path): string
    {
        return rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
    }

    private function decodePath(string $encodedPath): ?string
    {
        $padded = str_pad(strtr($encodedPath, '-_', '+/'), strlen($encodedPath) + ((4 - strlen($encodedPath) % 4) % 4), '=');
        $decoded = base64_decode($padded, true);

        if (! is_string($decoded)) {
            return null;
        }

        $normalized = $this->normalizePath($decoded);

        return $normalized !== '' ? $normalized : null;
    }
}
