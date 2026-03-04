<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Language;
use App\Models\Movie;
use App\Models\Vj;
use App\Support\MovieCatalogFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BrowseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = MovieCatalogFilters::normalize($request->validate(MovieCatalogFilters::validationRules()));

        $query = Movie::query()
            ->published()
            ->with(['language', 'vj', 'asset', 'genres'])
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->withCount([
                'favorites',
                'views as weekly_views_count' => fn (Builder $builder) => $builder->where('created_at', '>=', now()->subDays(7)),
            ]);

        MovieCatalogFilters::apply($query, $filters);

        $movies = $query->paginate(18)->withQueryString();

        return view('browse', [
            'movies' => $movies,
            'genres' => Genre::query()->orderBy('name')->get(),
            'languages' => Language::query()->orderBy('name')->get(),
            'vjs' => Vj::query()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }
}
