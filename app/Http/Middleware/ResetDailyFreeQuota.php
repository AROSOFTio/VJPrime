<?php

namespace App\Http\Middleware;

use App\Services\FreeQuotaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResetDailyFreeQuota
{
    public function __construct(private readonly FreeQuotaService $freeQuotaService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $this->freeQuotaService->resetIfExpired($request->user());
        }

        return $next($request);
    }
}
