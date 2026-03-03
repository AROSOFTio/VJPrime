<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Movie $movie): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['nullable', 'string', 'max:1000'],
        ]);

        auth()->user()->reviews()->updateOrCreate(
            ['movie_id' => $movie->id],
            $validated
        );

        return back()->with('status', 'Review saved.');
    }
}
