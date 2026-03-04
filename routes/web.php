<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GenreController as AdminGenreController;
use App\Http\Controllers\Admin\LanguageController as AdminLanguageController;
use App\Http\Controllers\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VjController as AdminVjController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\OnlineUsersController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/browse', [BrowseController::class, 'index'])->name('browse');
Route::get('/online-users-count', [OnlineUsersController::class, 'count'])->name('online-users.count');
Route::get('/movies/{slug}', [MovieController::class, 'show'])->name('movies.show');

Route::middleware(['auth', 'reset.quota'])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('home'))->name('dashboard');
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/movies/{slug}/play', [PlayerController::class, 'show'])
        ->middleware('playback.quota')
        ->name('player.show');

    Route::post('/movies/{movie}/favorite', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/movies/{movie}/favorite', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::post('/movies/{movie}/review', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/movies/{movie}/download-link', [DownloadController::class, 'createLink'])->name('downloads.link');
    Route::get('/billing/upgrade', [BillingController::class, 'upgrade'])->name('billing.upgrade');
    Route::post('/billing/pesapal/checkout', [BillingController::class, 'checkout'])->name('billing.pesapal.checkout');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::match(['get', 'post'], '/billing/pesapal/callback', [BillingController::class, 'callback'])
    ->name('billing.pesapal.callback');
Route::match(['get', 'post'], '/billing/pesapal/ipn', [BillingController::class, 'ipn'])
    ->name('billing.pesapal.ipn');

Route::get('/stream/{movie}/master.m3u8', [StreamController::class, 'playlist'])
    ->name('stream.playlist')
    ->middleware('signed');
Route::get('/stream/{movie}/asset/{encodedPath}', [StreamController::class, 'asset'])
    ->where('encodedPath', '[A-Za-z0-9\-_]+')
    ->name('stream.asset')
    ->middleware('signed');
Route::get('/download/{movie}', [StreamController::class, 'download'])
    ->name('stream.download')
    ->middleware('signed');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'can:access-admin-panel'])
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/reports', [AdminReportController::class, 'index'])
            ->name('reports.index')
            ->middleware('can:view-reports');
        Route::get('/reports/revenue', [AdminReportController::class, 'revenue'])
            ->name('reports.revenue')
            ->middleware('can:view-reports');
        Route::get('/reports/content', [AdminReportController::class, 'content'])
            ->name('reports.content')
            ->middleware('can:view-reports');
        Route::get('/reports/users', [AdminReportController::class, 'users'])
            ->name('reports.users')
            ->middleware('can:view-reports');
        Route::get('/reports/export/{section}', [AdminReportController::class, 'export'])
            ->name('reports.export')
            ->where('section', 'revenue|content|users')
            ->middleware('can:view-reports');

        Route::resource('users', AdminUserController::class)
            ->except('show')
            ->middleware('can:manage-users');

        Route::resource('movies', AdminMovieController::class)
            ->except(['show', 'destroy'])
            ->middleware('can:manage-content');
        Route::delete('/movies/{movie}', [AdminMovieController::class, 'destroy'])
            ->name('movies.destroy')
            ->middleware('can:delete-content');

        Route::resource('genres', AdminGenreController::class)
            ->except(['show', 'destroy'])
            ->middleware('can:manage-content');
        Route::delete('/genres/{genre}', [AdminGenreController::class, 'destroy'])
            ->name('genres.destroy')
            ->middleware('can:delete-content');

        Route::resource('languages', AdminLanguageController::class)
            ->except(['show', 'destroy'])
            ->middleware('can:manage-content');
        Route::delete('/languages/{language}', [AdminLanguageController::class, 'destroy'])
            ->name('languages.destroy')
            ->middleware('can:delete-content');

        Route::resource('vjs', AdminVjController::class)
            ->except(['show', 'destroy'])
            ->middleware('can:manage-content');
        Route::delete('/vjs/{vj}', [AdminVjController::class, 'destroy'])
            ->name('vjs.destroy')
            ->middleware('can:delete-content');
    });

require __DIR__.'/auth.php';
