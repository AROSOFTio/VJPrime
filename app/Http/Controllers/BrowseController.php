<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Language;
use App\Models\Movie;
use App\Models\Vj;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BrowseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'genre' => ['nullable', 'string', 'max:120'],
            'language' => ['nullable', 'string', 'max:120'],
            'vj' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'in:movie,series'],
            'sort' => ['nullable', 'in:trending,new,rating'],
        ]);

        $query = Movie::query()
            ->published()
            ->with(['language', 'vj', 'asset', 'genres'])
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->withCount([
                'favorites',
                'views as weekly_views_count' => fn (Builder $builder) => $builder->where('created_at', '>=', now()->subDays(7)),
            ]);

        if ($search = ($filters['search'] ?? null)) {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($genre = ($filters['genre'] ?? null)) {
            $query->whereHas('genres', function (Builder $builder) use ($genre) {
                $builder
                    ->where('genres.slug', $genre)
                    ->orWhere('genres.id', $genre);
            });
        }

        if ($language = ($filters['language'] ?? null)) {
            $query->whereHas('language', function (Builder $builder) use ($language) {
                $builder
                    ->where('languages.code', $language)
                    ->orWhere('languages.id', $language);
            });
        }

        if ($vj = ($filters['vj'] ?? null)) {
            $query->whereHas('vj', function (Builder $builder) use ($vj) {
                $builder
                    ->where('vjs.slug', $vj)
                    ->orWhere('vjs.id', $vj);
            });
        }

        if ($type = ($filters['type'] ?? null)) {
            $query->where('content_type', $type);
        }

        $sort = $filters['sort'] ?? 'trending';

        match ($sort) {
            'new' => $query->latest('published_at'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('published_at'),
            default => $query
                ->orderByDesc('weekly_views_count')
                ->orderByDesc('favorites_count')
                ->orderByDesc('published_at'),
        };

        $movies = $query->paginate(18)->withQueryString();

        return view('browse', [
            'movies' => $movies,
            'genres' => Genre::query()->orderBy('name')->get(),
            'languages' => Language::query()->orderBy('name')->get(),
            'vjs' => Vj::query()->orderBy('name')->get(),
            'filters' => $filters + ['sort' => $sort],
        ]);
    }
}
