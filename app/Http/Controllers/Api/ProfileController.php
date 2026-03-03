<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FreeQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly FreeQuotaService $freeQuotaService) {}

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['profile', 'favoriteMovies:id,title,slug,poster_url']);
        $remaining = $this->freeQuotaService->remaining($user);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'subscription_status' => $user->subscription_status,
            'profile' => $user->profile,
            'free_quota' => [
                'daily_limit_seconds' => (int) config('streaming.free.daily_seconds', 1800),
                'used_seconds' => $user->isPremium() ? 0 : ($user->daily_free_seconds_used ?? 0),
                'remaining_seconds' => $remaining,
                'reset_at' => $this->freeQuotaService->nextResetAt($user)?->toIso8601String(),
            ],
            'favorites' => $user->favoriteMovies,
        ]);
    }
}
