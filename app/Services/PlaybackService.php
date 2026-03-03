<?php

namespace App\Services;

use App\Models\Movie;
use App\Models\User;
use App\Models\View;
use App\Models\WatchProgress;

class PlaybackService
{
    public function start(User $user, Movie $movie, string $deviceHash, ?string $ip = null): View
    {
        return View::create([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'started_at' => now(),
            'seconds_watched' => 0,
            'device_hash' => $deviceHash,
            'ip' => $ip,
        ]);
    }

    public function heartbeat(
        User $user,
        Movie $movie,
        int $secondsDelta,
        int $lastPositionSeconds,
        ?int $viewId,
        string $deviceHash,
        ?string $ip = null
    ): View {
        $view = $this->resolveView($user, $movie, $viewId);

        if (! $view) {
            $view = $this->start($user, $movie, $deviceHash, $ip);
        }

        $view->forceFill([
            'seconds_watched' => $view->seconds_watched + max(0, $secondsDelta),
            'device_hash' => $deviceHash,
            'ip' => $ip,
        ])->save();

        $progress = WatchProgress::query()->firstOrNew([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
        ]);

        $newTotal = ($progress->seconds_watched_total ?? 0) + max(0, $secondsDelta);
        $isCompleted = $lastPositionSeconds >= $movie->duration_seconds && $movie->duration_seconds > 0;

        $progress->fill([
            'last_position_seconds' => max(0, $lastPositionSeconds),
            'seconds_watched_total' => $newTotal,
            'completed_at' => $isCompleted ? now() : $progress->completed_at,
        ]);

        $progress->save();

        if ($isCompleted && ! $view->completed_at) {
            $view->forceFill(['completed_at' => now()])->save();
        }

        return $view;
    }

    public function stop(
        User $user,
        Movie $movie,
        int $lastPositionSeconds,
        bool $completed,
        ?int $viewId
    ): ?View {
        $view = $this->resolveView($user, $movie, $viewId);

        $progress = WatchProgress::query()->firstOrNew([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
        ]);

        $progress->fill([
            'last_position_seconds' => max($progress->last_position_seconds ?? 0, $lastPositionSeconds),
            'completed_at' => $completed ? now() : $progress->completed_at,
        ])->save();

        if ($view) {
            $view->forceFill([
                'completed_at' => $completed ? now() : $view->completed_at,
            ])->save();
        }

        return $view;
    }

    private function resolveView(User $user, Movie $movie, ?int $viewId): ?View
    {
        if ($viewId) {
            return View::query()
                ->where('id', $viewId)
                ->where('user_id', $user->id)
                ->where('movie_id', $movie->id)
                ->first();
        }

        return View::query()
            ->where('user_id', $user->id)
            ->where('movie_id', $movie->id)
            ->latest('id')
            ->first();
    }
}
