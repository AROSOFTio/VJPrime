<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\User;
use App\Services\DeviceFingerprintService;
use App\Services\DownloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

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

        $disk = config('filesystems.default', 'local');
        abort_unless(Storage::disk($disk)->exists($path), 404, 'Playlist file not found.');

        return response(Storage::disk($disk)->get($path), 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            'Cache-Control' => 'private, max-age=60',
        ]);
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
}
