<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Services\DeviceFingerprintService;
use App\Services\DownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function __construct(
        private readonly DownloadService $downloadService,
        private readonly DeviceFingerprintService $deviceFingerprintService
    ) {}

    public function createLink(Request $request, Movie $movie): JsonResponse
    {
        $movie->loadMissing('asset');

        $check = $this->downloadService->canDownload($request->user());
        if (! $check['allowed']) {
            return response()->json(['message' => $check['message']], 403);
        }

        $signedUrl = $this->downloadService->signedUrl(
            $request->user(),
            $movie,
            $this->deviceFingerprintService->fromRequest($request)
        );

        if (! $signedUrl) {
            return response()->json(['message' => 'No downloadable file for this movie'], 404);
        }

        return response()->json([
            'download_url' => $signedUrl,
            'expires_in_minutes' => (int) config('streaming.downloads.signed_url_minutes', 10),
        ]);
    }
}
