<?php

namespace App\Http\Middleware;

use App\Services\FreeQuotaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlaybackQuota
{
    public function __construct(private readonly FreeQuotaService $freeQuotaService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $remaining = $this->freeQuotaService->remaining($user);

        if (! $user->isPremium() && $remaining <= 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Daily free limit reached',
                    'remaining_seconds' => 0,
                ], 402);
            }

            return redirect()
                ->route('account.index')
                ->with('error', 'Daily free limit reached. Upgrade to premium to continue.');
        }

        return $next($request);
    }
}
