<?php

namespace App\Http\Controllers;

use App\Services\FreeQuotaService;
use Illuminate\Contracts\View\View;

class AccountController extends Controller
{
    public function __construct(private readonly FreeQuotaService $freeQuotaService) {}

    public function index(): View
    {
        $user = auth()->user();
        $remaining = $this->freeQuotaService->remaining($user);
        $limit = (int) config('streaming.free.daily_seconds', 1800);
        $used = $user->isPremium() ? 0 : max($limit - $remaining, 0);

        return view('account.index', [
            'user' => $user->load('profile'),
            'used' => $used,
            'remaining' => $remaining,
            'limit' => $limit,
            'resetAt' => $this->freeQuotaService->nextResetAt($user),
            'favoritesCount' => $user->favorites()->count(),
            'downloadsCount' => $user->downloads()->where('created_at', '>=', now()->subDay())->count(),
        ]);
    }
}
