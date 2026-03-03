<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Services\DeviceFingerprintService;
use App\Services\FreeQuotaService;
use App\Services\PlaybackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PlaybackController extends Controller
{
    public function __construct(
        private readonly PlaybackService $playbackService,
        private readonly FreeQuotaService $freeQuotaService,
        private readonly DeviceFingerprintService $deviceFingerprintService
    ) {}

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
        ]);

        $user = $request->user();
        $movie = Movie::query()->published()->with('asset')->findOrFail($validated['movie_id']);
        $remaining = $this->freeQuotaService->remaining($user);

        if (! $user->isPremium() && $remaining <= 0) {
            return response()->json([
                'message' => 'Daily free limit reached',
                'remaining_seconds' => 0,
                'reset_at' => $this->freeQuotaService->nextResetAt($user)?->toIso8601String(),
            ], 402);
        }

        $deviceHash = $this->deviceFingerprintService->fromRequest($request);
        $view = $this->playbackService->start($user, $movie, $deviceHash, $request->ip());
        $playlistUrl = URL::temporarySignedRoute(
            'stream.playlist',
            now()->addMinutes((int) config('streaming.signed_playlist_minutes', 10)),
            [
                'movie' => $movie->id,
                'user' => $user->id,
                'device' => $deviceHash,
            ]
        );

        return response()->json([
            'view_id' => $view->id,
            'hls_url' => $playlistUrl,
            'remaining_seconds' => $remaining,
            'quota_limit_seconds' => (int) config('streaming.free.daily_seconds', 1800),
            'is_premium' => $user->isPremium(),
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
            'view_id' => ['nullable', 'integer'],
            'seconds_watched_delta' => ['required', 'integer', 'min:1', 'max:120'],
            'last_position_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $movie = Movie::query()->published()->findOrFail($validated['movie_id']);
        $deviceHash = $this->deviceFingerprintService->fromRequest($request);
        $secondsToApply = (int) $validated['seconds_watched_delta'];

        if (! $user->isPremium()) {
            $quota = $this->freeQuotaService->consume($user, $secondsToApply, $deviceHash);
            $secondsToApply = $quota['consumed'];

            if ($secondsToApply <= 0) {
                return response()->json([
                    'message' => 'Daily free limit reached',
                    'remaining_seconds' => 0,
                    'reset_at' => $quota['reset_at'],
                ], 402);
            }
        }

        $view = $this->playbackService->heartbeat(
            $user,
            $movie,
            $secondsToApply,
            (int) $validated['last_position_seconds'],
            $validated['view_id'] ?? null,
            $deviceHash,
            $request->ip()
        );

        $remaining = $this->freeQuotaService->remaining($user);

        return response()->json([
            'view_id' => $view->id,
            'remaining_seconds' => $remaining,
            'is_premium' => $user->isPremium(),
            'message' => (! $user->isPremium() && $remaining <= 0) ? 'Daily free limit reached' : 'ok',
        ], (! $user->isPremium() && $remaining <= 0) ? 402 : 200);
    }

    public function stop(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'movie_id' => ['required', 'exists:movies,id'],
            'view_id' => ['nullable', 'integer'],
            'last_position_seconds' => ['required', 'integer', 'min:0'],
            'completed' => ['nullable', 'boolean'],
        ]);

        $movie = Movie::query()->published()->findOrFail($validated['movie_id']);
        $view = $this->playbackService->stop(
            $request->user(),
            $movie,
            (int) $validated['last_position_seconds'],
            (bool) ($validated['completed'] ?? false),
            $validated['view_id'] ?? null
        );

        return response()->json([
            'message' => 'Playback stopped',
            'view_id' => $view?->id,
        ]);
    }
}
