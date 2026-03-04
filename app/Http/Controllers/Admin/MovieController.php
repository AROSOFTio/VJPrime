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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MovieController extends Controller
{
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
        $movie->asset()->updateOrCreate([], $this->assetPayload($request, $movie, $validated));

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

        $movie->asset()->updateOrCreate([], $this->assetPayload($request, $movie, $validated));

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
            'content_type' => ['required', 'in:movie,series'],
            'series_title' => ['nullable', 'string', 'max:255'],
            'season_number' => ['nullable', 'integer', 'min:1'],
            'episode_number' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'poster_url' => ['nullable', 'string', 'max:2048'],
            'backdrop_url' => ['nullable', 'string', 'max:2048'],
            'poster_file' => ['nullable', 'image', 'max:5120'],
            'backdrop_file' => ['nullable', 'image', 'max:8192'],
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
            'hls_master_path' => ['nullable', 'string', 'max:2048'],
            'hls_master_upload' => ['nullable', 'file', 'max:20480'],
            'preview_clip_path' => ['nullable', 'string', 'max:2048'],
            'preview_clip_upload' => ['nullable', 'file', 'max:204800'],
            'download_file_path' => ['nullable', 'string', 'max:2048'],
            'download_file_upload' => ['nullable', 'file', 'max:512000'],
            'renditions_json' => ['nullable', 'string', 'max:5000'],
            'size_bytes' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);
        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
        $validated['content_type'] = $validated['content_type'] ?? 'movie';
        $validated['series_title'] = $validated['content_type'] === 'series'
            ? ($validated['series_title'] ?: $validated['title'])
            : null;
        $validated['season_number'] = $validated['content_type'] === 'series'
            ? ($validated['season_number'] ?? 1)
            : null;
        $validated['episode_number'] = $validated['content_type'] === 'series'
            ? ($validated['episode_number'] ?? 1)
            : null;
        $validated['renditions_json'] = $validated['renditions_json']
            ? array_values(array_filter(array_map('trim', explode(',', $validated['renditions_json']))))
            : [];

        if ($request->file('poster_file') instanceof UploadedFile) {
            $validated['poster_url'] = Storage::disk('public')->url(
                $request->file('poster_file')->store('movies/posters', 'public')
            );
        }

        if ($request->file('backdrop_file') instanceof UploadedFile) {
            $validated['backdrop_url'] = Storage::disk('public')->url(
                $request->file('backdrop_file')->store('movies/backdrops', 'public')
            );
        }

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        return $validated;
    }

    private function assetPayload(Request $request, Movie $movie, array $validated): array
    {
        $payload = Arr::only($validated, [
            'hls_master_path',
            'preview_clip_path',
            'download_file_path',
            'renditions_json',
            'size_bytes',
        ]);

        if ($request->file('hls_master_upload') instanceof UploadedFile) {
            $payload['hls_master_path'] = $request->file('hls_master_upload')->store("movies/streams/{$movie->id}", config('filesystems.default'));
        }

        if ($request->file('preview_clip_upload') instanceof UploadedFile) {
            $payload['preview_clip_path'] = Storage::disk('public')->url(
                $request->file('preview_clip_upload')->store("movies/previews/{$movie->id}", 'public')
            );
        }

        if ($request->file('download_file_upload') instanceof UploadedFile) {
            $payload['download_file_path'] = $request->file('download_file_upload')->store("movies/downloads/{$movie->id}", config('filesystems.default'));
            $payload['size_bytes'] = $request->file('download_file_upload')->getSize();
        }

        $existingHlsPath = $movie->asset?->hls_master_path;
        if (empty($payload['hls_master_path']) && empty($existingHlsPath)) {
            throw ValidationException::withMessages([
                'hls_master_path' => 'Provide HLS master path or upload a master playlist file.',
            ]);
        }

        if (empty($payload['hls_master_path']) && ! empty($existingHlsPath)) {
            $payload['hls_master_path'] = $existingHlsPath;
        }

        return $payload;
    }
}
