<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\RedirectResponse;

class FavoriteController extends Controller
{
    public function store(Movie $movie): RedirectResponse
    {
        auth()->user()->favorites()->firstOrCreate([
            'movie_id' => $movie->id,
        ], [
            'created_at' => now(),
        ]);

        return back()->with('status', 'Added to your list.');
    }

    public function destroy(Movie $movie): RedirectResponse
    {
        auth()->user()->favorites()->where('movie_id', $movie->id)->delete();

        return back()->with('status', 'Removed from your list.');
    }
}
