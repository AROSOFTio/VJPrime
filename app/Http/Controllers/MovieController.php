<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Contracts\View\View;

class MovieController extends Controller
{
    public function show(string $slug): View
    {
        $movie = Movie::query()
            ->published()
            ->with([
                'language',
                'vj',
                'asset',
                'genres',
                'reviews.user',
            ])
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->withCount('reviews')
            ->where('slug', $slug)
            ->firstOrFail();

        $isFavorite = false;
        $watchProgress = null;

        if (auth()->check()) {
            $isFavorite = auth()->user()->favorites()
                ->where('movie_id', $movie->id)
                ->exists();

            $watchProgress = auth()->user()->watchProgress()
                ->where('movie_id', $movie->id)
                ->first();
        }

        $related = Movie::query()
            ->published()
            ->with(['asset', 'language', 'vj'])
            ->where('id', '!=', $movie->id)
            ->where(function ($query) use ($movie) {
                $query
                    ->where('language_id', $movie->language_id)
                    ->orWhere('vj_id', $movie->vj_id)
                    ->orWhereHas('genres', fn ($g) => $g->whereIn('genres.id', $movie->genres->pluck('id')));
            })
            ->take(8)
            ->get();

        return view('movies.show', [
            'movie' => $movie,
            'isFavorite' => $isFavorite,
            'watchProgress' => $watchProgress,
            'related' => $related,
        ]);
    }
}
