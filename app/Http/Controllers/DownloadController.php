<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Services\DeviceFingerprintService;
use App\Services\DownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function __construct(
        private readonly DownloadService $downloadService,
        private readonly DeviceFingerprintService $deviceFingerprintService
    ) {}

    public function createLink(Request $request, Movie $movie): JsonResponse|RedirectResponse
    {
        $movie->loadMissing('asset');
        $user = $request->user();

        $downloadCheck = $this->downloadService->canDownload($user);
        if (! $downloadCheck['allowed']) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $downloadCheck['message']], 403);
            }

            return back()->with('error', $downloadCheck['message']);
        }

        $deviceHash = $this->deviceFingerprintService->fromRequest($request);
        $signedUrl = $this->downloadService->signedUrl($user, $movie, $deviceHash);

        if (! $signedUrl) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No downloadable file for this movie.'], 404);
            }

            return back()->with('error', 'No downloadable file for this movie.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'download_url' => $signedUrl,
                'expires_in_minutes' => (int) config('streaming.downloads.signed_url_minutes', 10),
            ]);
        }

        return redirect()->away($signedUrl);
    }
}
