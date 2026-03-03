<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function store(Request $request, Movie $movie): JsonResponse
    {
        $request->user()->favorites()->firstOrCreate([
            'movie_id' => $movie->id,
        ], [
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Added to favorites'], 201);
    }

    public function destroy(Request $request, Movie $movie): JsonResponse
    {
        $request->user()->favorites()->where('movie_id', $movie->id)->delete();

        return response()->json(['message' => 'Removed from favorites']);
    }
}
