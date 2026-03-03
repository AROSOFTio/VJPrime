<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Services\TrendingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function __construct(private readonly TrendingService $trendingService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'genre' => ['nullable', 'string', 'max:120'],
            'language' => ['nullable', 'string', 'max:120'],
            'vj' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:trending,new,rating'],
        ]);

        $query = Movie::query()
            ->published()
            ->with(['language', 'vj', 'genres', 'asset'])
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->withCount([
                'reviews',
                'favorites',
                'views as weekly_views_count' => fn (Builder $builder) => $builder->where('created_at', '>=', now()->subDays(7)),
            ]);

        if ($search = ($filters['search'] ?? null)) {
            $query->where(fn (Builder $builder) => $builder
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        if ($genre = ($filters['genre'] ?? null)) {
            $query->whereHas('genres', fn (Builder $builder) => $builder->where('slug', $genre)->orWhere('id', $genre));
        }

        if ($language = ($filters['language'] ?? null)) {
            $query->whereHas('language', fn (Builder $builder) => $builder->where('code', $language)->orWhere('id', $language));
        }

        if ($vj = ($filters['vj'] ?? null)) {
            $query->whereHas('vj', fn (Builder $builder) => $builder->where('slug', $vj)->orWhere('id', $vj));
        }

        $sort = $filters['sort'] ?? 'trending';
        match ($sort) {
            'new' => $query->latest('published_at'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('published_at'),
            default => $query->orderByDesc('weekly_views_count')->orderByDesc('favorites_count')->orderByDesc('published_at'),
        };

        $movies = $query->paginate(15)->withQueryString();

        return response()->json($movies);
    }

    public function show(string $slug): JsonResponse
    {
        $movie = Movie::query()
            ->published()
            ->with(['language', 'vj', 'genres', 'asset', 'reviews.user'])
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->withCount(['reviews', 'favorites'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($movie);
    }

    public function trending(): JsonResponse
    {
        return response()->json($this->trendingService->getTrending(20));
    }
}
