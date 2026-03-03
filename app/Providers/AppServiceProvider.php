<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth-api', function (Request $request) {
            return Limit::perMinute(8)->by($request->ip());
        });

        RateLimiter::for('heartbeat', function (Request $request) {
            $key = implode('|', [
                $request->user()?->id ?? 'guest',
                $request->header('X-Device-Id', $request->ip()),
            ]);

            return Limit::perMinute(180)->by($key);
        });
    }
}
