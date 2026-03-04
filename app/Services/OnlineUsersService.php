<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OnlineUsersService
{
    public function count(int $windowMinutes = 5): int
    {
        if ((string) config('session.driver') !== 'database') {
            return 0;
        }

        try {
            if (! Schema::hasTable('sessions')) {
                return 0;
            }

            $threshold = now()->subMinutes(max(1, $windowMinutes))->timestamp;

            return (int) DB::table('sessions')
                ->where('last_activity', '>=', $threshold)
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
