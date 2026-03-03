<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Movie;
use App\Services\TrendingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function __construct(private readonly TrendingService $trendingService) {}

    public function index(): View
    {
        $trending = $this->trendingService->getTrending(12);
        $featured = Movie::query()
            ->published()
            ->where('is_featured', true)
            ->with(['language', 'vj', 'asset', 'genres'])
            ->latest('published_at')
            ->take(6)
            ->get();

        $continueWatching = collect();
        $yourList = collect();

        if (auth()->check()) {
            $user = auth()->user();

            $continueWatching = $user->watchProgress()
                ->with('movie.asset', 'movie.language', 'movie.vj')
                ->orderByDesc('updated_at')
                ->take(8)
                ->get();

            $yourList = $user->favoriteMovies()
                ->with(['asset', 'language', 'vj'])
                ->published()
                ->latest('favorites.created_at')
                ->take(12)
                ->get();
        }

        $genreRows = Genre::query()
            ->with([
                'movies' => fn ($query) => $query
                    ->published()
                    ->with(['asset', 'language', 'vj'])
                    ->latest('published_at')
                    ->take(12),
            ])
            ->get()
            ->filter(fn (Genre $genre) => $genre->movies->isNotEmpty())
            ->values();

        $wallpaperPosters = $this->wallpaperPosters($trending, $featured);

        return view('home', [
            'trending' => $trending,
            'featured' => $featured,
            'continueWatching' => $continueWatching,
            'yourList' => $yourList,
            'genreRows' => $genreRows,
            'wallpaperPosters' => $wallpaperPosters,
        ]);
    }

    private function wallpaperPosters(Collection $trending, Collection $featured): Collection
    {
        return $trending
            ->concat($featured)
            ->pluck('poster_url')
            ->filter()
            ->unique()
            ->take(10)
            ->values();
    }
}
