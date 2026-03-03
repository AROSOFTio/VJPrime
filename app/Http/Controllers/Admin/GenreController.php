<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GenreController extends Controller
{
    public function index(): View
    {
        return view('admin.genres.index', [
            'genres' => Genre::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.genres.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:genres,name'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:genres,slug'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        Genre::create($validated);

        return redirect()->route('admin.genres.index')->with('status', 'Genre created.');
    }

    public function edit(Genre $genre): View
    {
        return view('admin.genres.edit', compact('genre'));
    }

    public function update(Request $request, Genre $genre): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', "unique:genres,name,{$genre->id}"],
            'slug' => ['nullable', 'string', 'max:120', "unique:genres,slug,{$genre->id}"],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        $genre->update($validated);

        return redirect()->route('admin.genres.index')->with('status', 'Genre updated.');
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        $genre->delete();

        return redirect()->route('admin.genres.index')->with('status', 'Genre deleted.');
    }
}
