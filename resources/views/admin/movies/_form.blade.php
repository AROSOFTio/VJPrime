@php
    $isEdit = isset($movie);
    $asset = $isEdit ? ($movie->asset ?? null) : null;
    $movieGenreIds = $isEdit ? $movie->genres->pluck('id')->all() : [];
    $selectedGenres = collect(old('genre_ids', $movieGenreIds))->map(fn ($id) => (int) $id)->all();
    $publishedAt = old('published_at', ($isEdit && isset($movie->published_at)) ? $movie->published_at?->format('Y-m-d\TH:i') : null);
    $selectedType = old('content_type', $movie->content_type ?? 'movie');
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

<div class="mb-3 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-100">
    <p class="font-medium">Quick upload guide</p>
    <ol class="mt-1 list-decimal space-y-1 pl-4">
        <li>Choose <strong>Movie</strong> or <strong>Series Episode</strong>, then fill the basic details.</li>
        <li>For streaming, provide <strong>HLS master path/URL</strong> OR upload <strong>HLS ZIP package</strong> (recommended).</li>
        <li>Add optional preview and download file (URL or upload), then click <strong>{{ $isEdit ? 'Update Movie' : 'Create Movie' }}</strong>.</li>
    </ol>
</div>

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
    <p class="mt-1 text-[11px] text-sky-200/80">
        Filesystem disk: <strong>{{ $diskInfo['name'] ?? 'local' }}</strong> (driver: {{ $diskInfo['driver'] ?? 'local' }}).
    </p>
    @if (! ($diskInfo['public_storage_linked'] ?? true))
        <p class="mt-1 text-[11px] text-amber-200">
            Public storage link is missing. Run <code>php artisan storage:link</code> to serve uploaded posters/previews.
        </p>
    @endif
    @if (! ($diskInfo['supports_hls_package'] ?? true))
        <p class="mt-1 text-[11px] text-amber-200">
            HLS ZIP extraction requires a local filesystem disk. For current disk, use HLS URL/path instead.
        </p>
    @endif
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="mb-1 block text-xs text-slate-300">Title *</label>
        <input type="text" name="title" value="{{ old('title', $movie->title ?? '') }}" placeholder="Movie or episode title" required class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

    <div class="sm:col-span-2">
        <label class="mb-1 block text-xs text-slate-300">Slug (optional)</label>
        <input type="text" name="slug" value="{{ old('slug', $movie->slug ?? '') }}" placeholder="Auto-generated from title if left blank" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Content type *</label>
        <select name="content_type" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            <option value="movie" @selected($selectedType === 'movie')>Movie</option>
            <option value="series" @selected($selectedType === 'series')>Series Episode</option>
        </select>
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Year</label>
        <input type="number" name="year" value="{{ old('year', $movie->year ?? '') }}" placeholder="e.g. 2026" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

    <div @class(['sm:col-span-2 grid gap-3 sm:grid-cols-3', 'hidden' => $selectedType !== 'series']) data-series-field>
        <div>
            <label class="mb-1 block text-xs text-slate-300">Series title</label>
            <input type="text" name="series_title" value="{{ old('series_title', $movie->series_title ?? '') }}" placeholder="e.g. NTV Drama S1" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-300">Season #</label>
            <input type="number" name="season_number" value="{{ old('season_number', $movie->season_number ?? '') }}" placeholder="1" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs text-slate-300">Episode #</label>
            <input type="number" name="episode_number" value="{{ old('episode_number', $movie->episode_number ?? '') }}" placeholder="1" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
        </div>
    </div>

    <div class="sm:col-span-2">
        <label class="mb-1 block text-xs text-slate-300">Description</label>
        <textarea name="description" rows="4" placeholder="Short summary of the movie or episode" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">{{ old('description', $movie->description ?? '') }}</textarea>
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Duration (seconds) *</label>
        <input type="number" name="duration_seconds" value="{{ old('duration_seconds', $movie->duration_seconds ?? 5400) }}" required placeholder="e.g. 5400 (1h 30m)" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Age rating</label>
        <input type="text" name="age_rating" value="{{ old('age_rating', $movie->age_rating ?? '') }}" placeholder="e.g. PG-13" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Language *</label>
        <select name="language_id" required class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            @foreach ($languages as $language)
                <option value="{{ $language->id }}" @selected((int) old('language_id', $movie->language_id ?? 0) === $language->id)>{{ $language->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">VJ *</label>
        <select name="vj_id" required class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            @foreach ($vjs as $vj)
                <option value="{{ $vj->id }}" @selected((int) old('vj_id', $movie->vj_id ?? 0) === $vj->id)>{{ $vj->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Status *</label>
        <select name="status" required class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            <option value="draft" @selected(old('status', $movie->status ?? 'draft') === 'draft')>Draft</option>
            <option value="published" @selected(old('status', $movie->status ?? 'draft') === 'published')>Published</option>
        </select>
    </div>

    <div>
        <label class="mb-1 block text-xs text-slate-300">Publish date/time (optional)</label>
        <input type="datetime-local" name="published_at" value="{{ $publishedAt }}" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

    <div class="sm:col-span-2">
        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="is_featured" value="1" @checked((bool) old('is_featured', $movie->is_featured ?? false))>
            Featured on homepage
        </label>
    </div>

    <div class="sm:col-span-2 border-t border-white/10 pt-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-300">Posters and Backdrop (optional)</p>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs text-slate-300">Poster URL</label>
                <input type="text" name="poster_url" value="{{ old('poster_url', $movie->poster_url ?? '') }}" placeholder="https://..." class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-300">Upload poster file</label>
                <input type="file" name="poster_file" accept="image/*" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-300">Backdrop URL</label>
                <input type="text" name="backdrop_url" value="{{ old('backdrop_url', $movie->backdrop_url ?? '') }}" placeholder="https://..." class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-300">Upload backdrop file</label>
                <input type="file" name="backdrop_file" accept="image/*" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <div class="sm:col-span-2 border-t border-white/10 pt-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-300">Streaming source (required)</p>
        <p class="mb-3 text-xs text-slate-400">
            Use one method: HLS path/URL, upload a <code>.m3u8</code> master playlist, or upload an HLS ZIP package.
        </p>
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs text-slate-300">HLS master path or URL</label>
                <input type="text" name="hls_master_path" value="{{ old('hls_master_path', $asset->hls_master_path ?? '') }}" placeholder="movies/streams/12/master.m3u8 or https://cdn.example.com/master.m3u8" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-300">Upload HLS master (.m3u8)</label>
                <input type="file" name="hls_master_upload" accept=".m3u8" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-300">Upload HLS ZIP package (recommended)</label>
                <input type="file" name="hls_package_upload" accept=".zip,application/zip" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
                <p class="mt-1 text-[11px] text-slate-400">ZIP should include master playlist + variant playlists + all segment files.</p>
            </div>
        </div>
    </div>

    <div class="sm:col-span-2 border-t border-white/10 pt-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-300">Preview clip (optional)</p>
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs text-slate-300">Preview clip path or URL</label>
                <input type="text" name="preview_clip_path" value="{{ old('preview_clip_path', $asset->preview_clip_path ?? '') }}" placeholder="https://... or movies/previews/12/clip.mp4" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs text-slate-300">Upload preview clip</label>
                <input type="file" name="preview_clip_upload" accept="video/mp4,video/webm,video/*" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <div class="sm:col-span-2 border-t border-white/10 pt-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-300">Download file (optional)</p>
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs text-slate-300">Download file path or URL</label>
                <input type="text" name="download_file_path" value="{{ old('download_file_path', $asset->download_file_path ?? '') }}" placeholder="movies/downloads/12/movie.mp4 or https://..." class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-300">Upload download file</label>
                <input type="file" name="download_file_upload" accept="video/mp4,video/x-matroska,video/*,.zip" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-300">File size (bytes, optional)</label>
                <input type="number" name="size_bytes" value="{{ old('size_bytes', $asset->size_bytes ?? '') }}" placeholder="Auto-filled on upload" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <div class="sm:col-span-2 border-t border-white/10 pt-4">
        <label class="mb-1 block text-xs text-slate-300">Renditions (optional)</label>
        <input type="text" name="renditions_json" value="{{ old('renditions_json', implode(',', $asset->renditions_json ?? [])) }}" placeholder="auto,360p,480p,720p,1080p" class="w-full rounded-md border border-white/10 bg-slate-950 px-3 py-2 text-sm">
    </div>

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
