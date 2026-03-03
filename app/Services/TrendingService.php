<?php

namespace App\Services;

use App\Models\Movie;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TrendingService
{
    public function getTrending(int $limit = 20): Collection
    {
        $limit = max(1, $limit);

        return Cache::remember("trending:movies:{$limit}", now()->addMinutes(10), function () use ($limit) {
            $movies = Movie::query()
                ->published()
                ->with(['language', 'vj', 'genres', 'asset'])
                ->withCount([
                    'views as daily_views_count' => fn ($query) => $query->where('created_at', '>=', now()->subDay()),
                    'views as weekly_views_count' => fn ($query) => $query->where('created_at', '>=', now()->subDays(7)),
                    'views as completions_count' => fn ($query) => $query
                        ->whereNotNull('completed_at')
                        ->where('created_at', '>=', now()->subDays(7)),
                    'favorites',
                ])
                ->withAvg('reviews as rating_avg', 'rating')
                ->get()
                ->map(function (Movie $movie): Movie {
                    $movie->trending_score =
                        ($movie->daily_views_count * 3) +
                        ($movie->weekly_views_count * 1.5) +
                        ($movie->completions_count * 4) +
                        ($movie->favorites_count * 2) +
                        (($movie->rating_avg ?? 0) * 5);

                    return $movie;
                })
                ->sortByDesc('trending_score')
                ->take($limit)
                ->values();

            return $movies;
        });
    }
}
