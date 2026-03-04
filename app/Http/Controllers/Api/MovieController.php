<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Support\MovieCatalogFilters;
use App\Services\TrendingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function __construct(private readonly TrendingService $trendingService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = MovieCatalogFilters::normalize($request->validate(MovieCatalogFilters::validationRules()));

        $query = Movie::query()
            ->published()
            ->with(['language', 'vj', 'genres', 'asset'])
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->withCount([
                'reviews',
                'favorites',
                'views as weekly_views_count' => fn (Builder $builder) => $builder->where('created_at', '>=', now()->subDays(7)),
            ]);

        MovieCatalogFilters::apply($query, $filters);

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
