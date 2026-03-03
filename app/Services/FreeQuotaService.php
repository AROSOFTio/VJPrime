<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class FreeQuotaService
{
    public function resetIfExpired(User $user): void
    {
        if ($user->isPremium()) {
            return;
        }

        $lastReset = $user->last_reset_at;

        if (! $lastReset || $lastReset->lte(now()->subDay())) {
            $user->forceFill([
                'daily_free_seconds_used' => 0,
                'last_reset_at' => now(),
            ])->save();
        }
    }

    public function remaining(User $user): int
    {
        if ($user->isPremium()) {
            return PHP_INT_MAX;
        }

        $this->resetIfExpired($user);
        $limit = (int) config('streaming.free.daily_seconds', 1800);

        return max($limit - $user->daily_free_seconds_used, 0);
    }

    public function consume(User $user, int $seconds, string $deviceHash): array
    {
        $seconds = max($seconds, 0);
        $this->resetIfExpired($user);

        $forceFill = [];

        if ($user->device_hash !== $deviceHash) {
            $forceFill['device_hash'] = $deviceHash;
        }

        if ($user->isPremium()) {
            if ($forceFill !== []) {
                $user->forceFill($forceFill)->save();
            }

            return [
                'allowed' => true,
                'consumed' => $seconds,
                'remaining' => PHP_INT_MAX,
                'reset_at' => null,
            ];
        }

        $remaining = $this->remaining($user);

        if ($remaining <= 0) {
            if ($forceFill !== []) {
                $user->forceFill($forceFill)->save();
            }

            return [
                'allowed' => false,
                'consumed' => 0,
                'remaining' => 0,
                'reset_at' => $this->nextResetAt($user)?->toIso8601String(),
            ];
        }

        $consumed = min($seconds, $remaining);
        $forceFill['daily_free_seconds_used'] = $user->daily_free_seconds_used + $consumed;
        $forceFill['last_reset_at'] = $user->last_reset_at ?: now();

        $user->forceFill($forceFill)->save();

        return [
            'allowed' => $seconds <= $remaining,
            'consumed' => $consumed,
            'remaining' => max($remaining - $consumed, 0),
            'reset_at' => $this->nextResetAt($user)?->toIso8601String(),
        ];
    }

    public function nextResetAt(User $user): ?Carbon
    {
        if ($user->isPremium() || ! $user->last_reset_at) {
            return null;
        }

        return $user->last_reset_at->copy()->addDay();
    }
}
