<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Services\FreeQuotaService;
use Illuminate\Contracts\View\View;

class PlayerController extends Controller
{
    public function __construct(private readonly FreeQuotaService $freeQuotaService) {}

    public function show(string $slug): View
    {
        $movie = Movie::query()
            ->published()
            ->with(['asset', 'language', 'vj'])
            ->where('slug', $slug)
            ->firstOrFail();

        $user = auth()->user();

        return view('player.show', [
            'movie' => $movie,
            'quotaRemaining' => $this->freeQuotaService->remaining($user),
            'quotaLimit' => (int) config('streaming.free.daily_seconds', 1800),
        ]);
    }
}
