@php
    $isEdit = isset($movie);
    $asset = $isEdit ? ($movie->asset ?? null) : null;
    $movieGenreIds = $isEdit ? $movie->genres->pluck('id')->all() : [];
    $selectedGenres = collect(old('genre_ids', $movieGenreIds))->map(fn ($id) => (int) $id)->all();
    $publishedAt = old('published_at', ($isEdit && isset($movie->published_at)) ? $movie->published_at?->format('Y-m-d\TH:i') : null);
@endphp

@if ($errors->any())
    <div class="mb-3 rounded-md border border-red-500/40 bg-red-500/10 px-3 py-2 text-xs text-red-100 sm:col-span-2">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-3 rounded-md border border-sky-500/30 bg-sky-500/10 px-3 py-2 text-xs text-sky-100">
    <p class="font-medium">Upload limits (effective on this server)</p>
    <p class="mt-1">
        HLS playlist: {{ number_format(($uploadLimits['hls_master'] ?? 0) / 1024, 1) }} MB |
        HLS ZIP package: {{ number_format(($uploadLimits['hls_package'] ?? 0) / 1024, 1) }} MB |
        Preview: {{ number_format(($uploadLimits['preview'] ?? 0) / 1024, 1) }} MB |
        Download: {{ number_format(($uploadLimits['download'] ?? 0) / 1024, 1) }} MB
    </p>
    <p class="mt-1 text-[11px] text-sky-200/80">
        PHP `upload_max_filesize`: {{ number_format(($uploadLimits['server_upload'] ?? 0) / 1024, 1) }} MB,
        `post_max_size`: {{ number_format(($uploadLimits['server_post'] ?? 0) / 1024, 1) }} MB.
    </p>
</div>

<div class="grid gap-3 sm:grid-cols-2">
    <input type="text" name="title" value="{{ old('title', $movie->title ?? '') }}" placeholder="Title" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2">
    <input type="text" name="slug" value="{{ old('slug', $movie->slug ?? '') }}" placeholder="Slug (optional)" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2">
    <select name="content_type" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
        <option value="movie" @selected(old('content_type', $movie->content_type ?? 'movie') === 'movie')>Movie</option>
        <option value="series" @selected(old('content_type', $movie->content_type ?? 'movie') === 'series')>Series Episode</option>
    </select>
    <input type="text" name="series_title" value="{{ old('series_title', $movie->series_title ?? '') }}" placeholder="Series title (for episodes)" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    <input type="number" name="season_number" value="{{ old('season_number', $movie->season_number ?? '') }}" placeholder="Season #" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    <input type="number" name="episode_number" value="{{ old('episode_number', $movie->episode_number ?? '') }}" placeholder="Episode #" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    <textarea name="description" rows="4" placeholder="Description" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2">{{ old('description', $movie->description ?? '') }}</textarea>

    <input type="text" name="poster_url" value="{{ old('poster_url', $movie->poster_url ?? '') }}" placeholder="Poster URL" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    <input type="file" name="poster_file" accept="image/*" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    <input type="text" name="backdrop_url" value="{{ old('backdrop_url', $movie->backdrop_url ?? '') }}" placeholder="Backdrop URL" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    <input type="file" name="backdrop_file" accept="image/*" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    <input type="number" name="year" value="{{ old('year', $movie->year ?? '') }}" placeholder="Year" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    <input type="number" name="duration_seconds" value="{{ old('duration_seconds', $movie->duration_seconds ?? 5400) }}" placeholder="Duration seconds" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    <input type="text" name="age_rating" value="{{ old('age_rating', $movie->age_rating ?? '') }}" placeholder="Age rating" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">

    <select name="language_id" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
        @foreach ($languages as $language)
            <option value="{{ $language->id }}" @selected((int) old('language_id', $movie->language_id ?? 0) === $language->id)>{{ $language->name }}</option>
        @endforeach
    </select>

    <select name="vj_id" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
        @foreach ($vjs as $vj)
            <option value="{{ $vj->id }}" @selected((int) old('vj_id', $movie->vj_id ?? 0) === $vj->id)>{{ $vj->name }}</option>
        @endforeach
    </select>

    <select name="status" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
        <option value="draft" @selected(old('status', $movie->status ?? 'draft') === 'draft')>Draft</option>
        <option value="published" @selected(old('status', $movie->status ?? 'draft') === 'published')>Published</option>
    </select>
    <input type="datetime-local" name="published_at" value="{{ $publishedAt }}" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">

    <label class="flex items-center gap-2 text-sm text-slate-300">
        <input type="checkbox" name="is_featured" value="1" @checked((bool) old('is_featured', $movie->is_featured ?? false))>
        Featured
    </label>

    <div class="sm:col-span-2">
        <p class="mb-2 text-xs text-slate-300">Genres</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($genres as $genre)
                <label class="flex items-center gap-2 rounded-md border border-white/10 bg-slate-950/70 px-2 py-1 text-xs">
                    <input type="checkbox" name="genre_ids[]" value="{{ $genre->id }}" @checked(in_array($genre->id, $selectedGenres, true))>
                    {{ $genre->name }}
                </label>
            @endforeach
        </div>
    </div>

    <input type="text" name="hls_master_path" value="{{ old('hls_master_path', $asset->hls_master_path ?? '') }}" placeholder="HLS master path or URL" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2">
    <input type="file" name="hls_master_upload" accept=".m3u8" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2">
    <input type="file" name="hls_package_upload" accept=".zip,application/zip" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2">

    <input type="text" name="preview_clip_path" value="{{ old('preview_clip_path', $asset->preview_clip_path ?? '') }}" placeholder="Preview clip path or URL" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2">
    <input type="file" name="preview_clip_upload" accept="video/mp4,video/webm,video/*" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2">

    <input type="text" name="download_file_path" value="{{ old('download_file_path', $asset->download_file_path ?? '') }}" placeholder="Download file path or URL" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2">
    <input type="file" name="download_file_upload" accept="video/mp4,video/x-matroska,video/*,.zip" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2">

    <input type="text" name="renditions_json" value="{{ old('renditions_json', implode(',', $asset->renditions_json ?? [])) }}" placeholder="Renditions comma-separated (auto,360p,480p...)" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm sm:col-span-2">
    <input type="number" name="size_bytes" value="{{ old('size_bytes', $asset->size_bytes ?? '') }}" placeholder="File size bytes" class="rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
</div>

<div class="mt-4 hidden rounded-md border border-sky-500/30 bg-slate-950/60 p-3" data-upload-progress-wrap>
    <div class="mb-2 flex items-center justify-between text-xs text-sky-100">
        <span data-upload-progress-label>Preparing upload...</span>
        <span data-upload-progress-percent>0%</span>
    </div>
    <div class="h-2 overflow-hidden rounded bg-white/10">
        <div class="h-full w-0 bg-sky-500 transition-all duration-200" data-upload-progress-bar></div>
    </div>
</div>

<button type="submit" class="mt-4 rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white" data-upload-submit>
    {{ $isEdit ? 'Update Movie' : 'Create Movie' }}
</button>
