<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Movie;
use App\Models\Vj;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MovieController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Movie::class, 'movie');
    }

    public function index(): View
    {
        $movies = Movie::query()
            ->with(['language', 'vj', 'genres'])
            ->latest()
            ->paginate(20);

        return view('admin.movies.index', compact('movies'));
    }

    public function create(): View
    {
        return view('admin.movies.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $movie = new Movie;

        $validated = $this->validateRequest($request, $movie);
        $validated['created_by'] = $request->user()->id;

        $movie->fill(Arr::except($validated, ['genre_ids', 'hls_master_path', 'preview_clip_path', 'download_file_path', 'renditions_json', 'size_bytes']));
        $movie->save();

        $movie->genres()->sync($validated['genre_ids'] ?? []);
        $movie->asset()->updateOrCreate([], Arr::only($validated, [
            'hls_master_path',
            'preview_clip_path',
            'download_file_path',
            'renditions_json',
            'size_bytes',
        ]));

        return redirect()->route('admin.movies.index')->with('status', 'Movie created.');
    }

    public function edit(Movie $movie): View
    {
        $movie->load(['genres', 'asset']);

        return view('admin.movies.edit', $this->formData() + compact('movie'));
    }

    public function update(Request $request, Movie $movie): RedirectResponse
    {
        $validated = $this->validateRequest($request, $movie);

        $movie->update(Arr::except($validated, ['genre_ids', 'hls_master_path', 'preview_clip_path', 'download_file_path', 'renditions_json', 'size_bytes']));
        $movie->genres()->sync($validated['genre_ids'] ?? []);

        $movie->asset()->updateOrCreate([], Arr::only($validated, [
            'hls_master_path',
            'preview_clip_path',
            'download_file_path',
            'renditions_json',
            'size_bytes',
        ]));

        return redirect()->route('admin.movies.index')->with('status', 'Movie updated.');
    }

    public function destroy(Movie $movie): RedirectResponse
    {
        $movie->delete();

        return redirect()->route('admin.movies.index')->with('status', 'Movie deleted.');
    }

    private function formData(): array
    {
        return [
            'languages' => Language::query()->orderBy('name')->get(),
            'vjs' => Vj::query()->orderBy('name')->get(),
            'genres' => Genre::query()->orderBy('name')->get(),
        ];
    }

    private function validateRequest(Request $request, Movie $movie): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('movies', 'slug')->ignore($movie->id)],
            'description' => ['nullable', 'string'],
            'poster_url' => ['nullable', 'url', 'max:2048'],
            'backdrop_url' => ['nullable', 'url', 'max:2048'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'duration_seconds' => ['required', 'integer', 'min:1'],
            'age_rating' => ['nullable', 'string', 'max:20'],
            'language_id' => ['required', 'exists:languages,id'],
            'vj_id' => ['required', 'exists:vjs,id'],
            'is_featured' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'genre_ids' => ['nullable', 'array'],
            'genre_ids.*' => ['exists:genres,id'],
            'hls_master_path' => ['required', 'string', 'max:2048'],
            'preview_clip_path' => ['nullable', 'string', 'max:2048'],
            'download_file_path' => ['nullable', 'string', 'max:2048'],
            'renditions_json' => ['nullable', 'string', 'max:5000'],
            'size_bytes' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);
        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
        $validated['renditions_json'] = $validated['renditions_json']
            ? array_values(array_filter(array_map('trim', explode(',', $validated['renditions_json']))))
            : [];

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        return $validated;
    }
}
