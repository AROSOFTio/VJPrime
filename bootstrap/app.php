<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'billing/pesapal/ipn',
            'billing/pesapal/callback',
        ]);

        $middleware->alias([
            'reset.quota' => \App\Http\Middleware\ResetDailyFreeQuota::class,
            'playback.quota' => \App\Http\Middleware\EnsurePlaybackQuota::class,
        ]);

        $middleware->api(prepend: [
            HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $largeUploadMessage = static function (): string {
            $uploadMax = (string) ini_get('upload_max_filesize');
            $postMax = (string) ini_get('post_max_size');

            return "Upload is larger than this server allows (upload_max_filesize={$uploadMax}, post_max_size={$postMax}). "
                .'Reduce file size or increase server limits.';
        };

        $exceptInput = [
            'poster_file',
            'backdrop_file',
            'hls_master_upload',
            'hls_package_upload',
            'preview_clip_upload',
            'download_file_upload',
        ];

        $exceptions->render(function (PostTooLargeException $exception, Request $request) use ($largeUploadMessage, $exceptInput) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $largeUploadMessage()], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
            }

            return back()
                ->withInput($request->except($exceptInput))
                ->withErrors(['upload' => $largeUploadMessage()]);
        });

        $exceptions->render(function (TokenMismatchException $exception, Request $request) use ($largeUploadMessage, $exceptInput) {
            if (! $request->is('admin/movies') && ! $request->is('admin/movies/*')) {
                return null;
            }

            $message = 'Session expired, CSRF token missing, or request payload was too large. '
                .$largeUploadMessage().' Refresh the page and try again.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 419);
            }

            return back()
                ->withInput($request->except($exceptInput))
                ->withErrors(['upload' => $message]);
        });
    })->create();
