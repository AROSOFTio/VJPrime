<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OnlineUsersService
{
    private const INDEX_CACHE_KEY = 'online-users:index';

    public function count(int $windowMinutes = 5, ?Request $request = null): int
    {
        $base = max(0, (int) config('streaming.online.base', 120));
        $windowMinutes = max(1, $windowMinutes);

        try {
            $trackedCount = $this->countTrackedVisitors($windowMinutes, $request);

            return $base + $trackedCount;
        } catch (\Throwable) {
            // Fallback to legacy session table behavior if cache tracking fails.
        }

        return $base + $this->countFromSessions($windowMinutes);
    }

    private function countTrackedVisitors(int $windowMinutes, ?Request $request = null): int
    {
        $now = now()->timestamp;
        $expiresAt = now()->addMinutes($windowMinutes)->timestamp;
        $ttl = now()->addMinutes($windowMinutes + 1);
        $index = Cache::get(self::INDEX_CACHE_KEY, []);

        if (! is_array($index)) {
            $index = [];
        }

        if ($request) {
            $visitorId = $this->visitorIdentifier($request);
            $visitorKey = $this->visitorCacheKey($visitorId);

            $index[$visitorKey] = $expiresAt;
            Cache::put($visitorKey, $expiresAt, $ttl);
        }

        $active = [];

        foreach ($index as $visitorKey => $expiryTimestamp) {
            if (! is_string($visitorKey)) {
                continue;
            }

            $cacheExpiry = (int) Cache::get($visitorKey, 0);
            $effectiveExpiry = max((int) $expiryTimestamp, $cacheExpiry);

            if ($effectiveExpiry >= $now) {
                $active[$visitorKey] = $effectiveExpiry;
            }
        }

        Cache::put(self::INDEX_CACHE_KEY, $active, now()->addHours(12));

        return count($active);
    }

    private function visitorIdentifier(Request $request): string
    {
        if ($request->user()) {
            return 'user:'.$request->user()->getAuthIdentifier();
        }

        $deviceId = trim((string) $request->header('X-Device-Id', ''));
        if ($deviceId !== '') {
            return 'device:'.Str::limit($deviceId, 120, '');
        }

        return 'guest:'.hash('sha256', implode('|', [
            $request->ip() ?? 'unknown-ip',
            substr((string) $request->userAgent(), 0, 240),
        ]));
    }

    private function visitorCacheKey(string $visitorId): string
    {
        return 'online-users:visitor:'.hash('sha256', $visitorId);
    }

    private function countFromSessions(int $windowMinutes): int
    {
        if ((string) config('session.driver') !== 'database') {
            return 0;
        }

        try {
            if (! Schema::hasTable('sessions')) {
                return 0;
            }

            $threshold = now()->subMinutes($windowMinutes)->timestamp;

            return (int) DB::table('sessions')
                ->where('last_activity', '>=', $threshold)
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
