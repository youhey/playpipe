<?php

use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Auth\LocalAdminLoginController;
use App\Http\Controllers\Episodes\EpisodeAudioController;
use App\Http\Controllers\Episodes\EpisodeDownloadController;
use App\Http\Controllers\Episodes\EpisodeIndexController;
use App\Http\Controllers\Episodes\EpisodeShowController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('Not Found', 404)
        ->header('Content-Type', 'text/plain; charset=UTF-8')
        ->header('Cache-Control', 'public, max-age=3600');
});

Route::get('/auth/google/redirect', [GoogleOAuthController::class, 'redirect'])
    ->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleOAuthController::class, 'callback'])
    ->name('auth.google.callback');
Route::get('/login', static fn () => redirect()->route('auth.google.redirect'))
    ->name('login');

Route::get('/_local/admin/login', LocalAdminLoginController::class)
    ->name('local.admin.login');

Route::middleware(['auth'])->group(function (): void {
    Route::get('/episodes', EpisodeIndexController::class)->name('episodes.index');
    Route::get('/episodes/{episode:episode_key}', EpisodeShowController::class)->name('episodes.show');
    Route::get('/episodes/{episode:episode_key}/audio', EpisodeAudioController::class)->name('episodes.audio');
    Route::get('/episodes/{episode:episode_key}/download', EpisodeDownloadController::class)->name('episodes.download');
});
