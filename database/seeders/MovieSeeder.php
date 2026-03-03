<?php

namespace Database\Seeders;

use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Movie;
use App\Models\MovieAsset;
use App\Models\Review;
use App\Models\User;
use App\Models\View;
use App\Models\Vj;
use App\Models\WatchProgress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $atesto = Language::query()->where('code', 'teo')->firstOrFail();
        $luganda = Language::query()->where('code', 'lg')->firstOrFail();
        $vjs = Vj::query()->get()->keyBy('slug');
        $genres = Genre::query()->get()->keyBy('slug');
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $users = User::query()->where('role', 'user')->get();

        $catalog = [
            [
                'title' => 'Aro Frontier',
                'slug' => 'aro-frontier',
                'description' => 'A fast-moving action story translated for Ateso audiences with a bold VJ narration style.',
                'language_id' => $atesto->id,
                'vj_id' => $vjs->get('vj-suldan')?->id ?? $vjs->first()->id,
                'genre_slugs' => ['action', 'thriller'],
            ],
            [
                'title' => 'Kampala Night Echo',
                'slug' => 'kampala-night-echo',
                'description' => 'A mystery drama retold in Luganda with high-stakes twists and emotional voice-over.',
                'language_id' => $luganda->id,
                'vj_id' => $vjs->get('vj-aro')?->id ?? $vjs->first()->id,
                'genre_slugs' => ['drama', 'thriller'],
            ],
            [
                'title' => 'Love in Soroti',
                'slug' => 'love-in-soroti',
                'description' => 'A romantic comedy where local language translation adds warmth and humor.',
                'language_id' => $atesto->id,
                'vj_id' => $vjs->get('vj-teso-star')?->id ?? $vjs->first()->id,
                'genre_slugs' => ['romance', 'comedy'],
            ],
            [
                'title' => 'Skyline Hunters',
                'slug' => 'skyline-hunters',
                'description' => 'A sci-fi chase story with a lively Luganda VJ interpretation.',
                'language_id' => $luganda->id,
                'vj_id' => $vjs->get('vj-suldan')?->id ?? $vjs->first()->id,
                'genre_slugs' => ['action', 'sci-fi'],
            ],
            [
                'title' => 'Broken Oath',
                'slug' => 'broken-oath',
                'description' => 'A courtroom drama translated in Ateso with gripping narration.',
                'language_id' => $atesto->id,
                'vj_id' => $vjs->get('vj-aro')?->id ?? $vjs->first()->id,
                'genre_slugs' => ['drama'],
            ],
            [
                'title' => 'Midnight Signal',
                'slug' => 'midnight-signal',
                'description' => 'A thriller packed with suspense and localized slang for Luganda viewers.',
                'language_id' => $luganda->id,
                'vj_id' => $vjs->get('vj-teso-star')?->id ?? $vjs->first()->id,
                'genre_slugs' => ['thriller', 'action'],
            ],
        ];

        foreach ($catalog as $index => $entry) {
            $movie = Movie::updateOrCreate(['slug' => $entry['slug']], [
                'title' => $entry['title'],
                'description' => $entry['description'],
                'poster_url' => "https://picsum.photos/seed/VJPrime-poster-{$index}/600/900",
                'backdrop_url' => "https://picsum.photos/seed/VJPrime-backdrop-{$index}/1600/900",
                'year' => 2018 + $index,
                'duration_seconds' => 5400 + ($index * 240),
                'age_rating' => Arr::random(['PG', 'PG-13', '16', '18']),
                'language_id' => $entry['language_id'],
                'vj_id' => $entry['vj_id'],
                'is_featured' => $index < 3,
                'status' => 'published',
                'published_at' => now()->subDays(20 - $index),
                'created_by' => $admin->id,
            ]);

            $movie->genres()->sync(
                collect($entry['genre_slugs'])
                    ->map(fn (string $slug) => $genres->get($slug)?->id)
                    ->filter()
                    ->values()
                    ->all()
            );

            MovieAsset::updateOrCreate(['movie_id' => $movie->id], [
                'hls_master_path' => 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8',
                'preview_clip_path' => 'https://storage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                'download_file_path' => 'https://storage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                'renditions_json' => ['auto', '360p', '480p', '720p', '1080p'],
                'size_bytes' => 250_000_000,
            ]);
        }

        foreach ($users as $user) {
            $movie = Movie::query()->inRandomOrder()->first();

            if (! $movie) {
                continue;
            }

            Favorite::updateOrCreate([
                'user_id' => $user->id,
                'movie_id' => $movie->id,
            ], [
                'created_at' => now(),
            ]);

            Review::updateOrCreate([
                'user_id' => $user->id,
                'movie_id' => $movie->id,
            ], [
                'rating' => random_int(3, 5),
                'body' => 'Great local translation and smooth streaming quality.',
            ]);

            WatchProgress::updateOrCreate([
                'user_id' => $user->id,
                'movie_id' => $movie->id,
            ], [
                'last_position_seconds' => 1200,
                'seconds_watched_total' => 1800,
                'completed_at' => null,
            ]);
        }

        Movie::query()->get()->each(function (Movie $movie): void {
            View::create([
                'user_id' => User::query()->where('role', 'user')->inRandomOrder()->value('id'),
                'movie_id' => $movie->id,
                'started_at' => now()->subMinutes(random_int(90, 4000)),
                'completed_at' => random_int(0, 1) ? now()->subMinutes(random_int(20, 80)) : null,
                'seconds_watched' => random_int(240, $movie->duration_seconds),
                'device_hash' => hash('sha256', (string) random_int(1, 999999)),
                'ip' => '127.0.0.1',
            ]);
        });
    }
}

