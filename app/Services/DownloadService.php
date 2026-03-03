<?php

namespace App\Services;

use App\Models\Download;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class DownloadService
{
    public function canDownload(User $user): array
    {
        if ($user->isPremium()) {
            return ['allowed' => true, 'message' => null];
        }

        if ((bool) config('streaming.downloads.premium_only', true)) {
            return ['allowed' => false, 'message' => 'Downloads are premium only.'];
        }

        $limit = (int) config('streaming.downloads.free_daily_limit', 1);
        $count = Download::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($count >= $limit) {
            return ['allowed' => false, 'message' => 'Daily download limit reached.'];
        }

        return ['allowed' => true, 'message' => null];
    }

    public function signedUrl(User $user, Movie $movie, string $deviceHash): ?string
    {
        $path = $movie->asset?->download_file_path;

        if (! $path) {
            return null;
        }

        return URL::temporarySignedRoute(
            'stream.download',
            now()->addMinutes((int) config('streaming.downloads.signed_url_minutes', 10)),
            [
                'movie' => $movie->id,
                'user' => $user->id,
                'device' => $deviceHash,
            ]
        );
    }

    public function trackDownload(User $user, Movie $movie, string $path, string $deviceHash, ?string $ip = null): void
    {
        Download::create([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'file_path' => $path,
            'created_at' => now(),
            'ip' => $ip,
            'device_hash' => $deviceHash,
        ]);
    }
}
