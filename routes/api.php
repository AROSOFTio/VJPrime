<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\PlaybackController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth-api');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-api');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/{slug}', [MovieController::class, 'show']);
Route::get('/trending', [MovieController::class, 'trending']);

Route::middleware(['auth:sanctum', 'reset.quota'])->group(function () {
    Route::post('/movies/{movie}/favorite', [FavoriteController::class, 'store']);
    Route::delete('/movies/{movie}/favorite', [FavoriteController::class, 'destroy']);
    Route::post('/movies/{movie}/review', [ReviewController::class, 'store']);
    Route::post('/movies/{movie}/download-link', [DownloadController::class, 'createLink']);

    Route::post('/playback/start', [PlaybackController::class, 'start'])->middleware('playback.quota');
    Route::post('/playback/heartbeat', [PlaybackController::class, 'heartbeat'])->middleware('throttle:heartbeat');
    Route::post('/playback/stop', [PlaybackController::class, 'stop']);
    Route::post('/billing/pesapal/checkout', [BillingController::class, 'checkout']);
    Route::get('/billing/payments', [BillingController::class, 'history']);

    Route::get('/me', [ProfileController::class, 'me']);
});
