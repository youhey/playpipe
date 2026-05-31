<?php

use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Auth\LocalAdminLoginController;
use App\Http\Controllers\Listen\ListenEpisodeAudioController;
use App\Http\Controllers\Listen\ListenEpisodeDownloadController;
use App\Http\Controllers\Listen\ListenEpisodeIndexController;
use App\Http\Controllers\Listen\ListenEpisodePlaybackController;
use App\Http\Controllers\Listen\ListenEpisodeShowController;
use App\Http\Controllers\Listen\ListenHomeController;
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

Route::middleware(['auth', 'viewer.allowed'])->prefix('listen')->name('listen.')->group(function (): void {
    Route::get('/', ListenHomeController::class)->name('home');
    Route::get('/episodes', ListenEpisodeIndexController::class)->name('episodes.index');
    Route::get('/episodes/{episode:episode_key}', ListenEpisodeShowController::class)->name('episodes.show');
    Route::get('/episodes/{episode:episode_key}/audio', ListenEpisodeAudioController::class)->name('episodes.audio');
    Route::get('/episodes/{episode:episode_key}/download', ListenEpisodeDownloadController::class)->name('episodes.download');
    Route::post('/episodes/{episode:episode_key}/playback/start', [ListenEpisodePlaybackController::class, 'start'])->name('episodes.playback.start');
    Route::patch('/episodes/{episode:episode_key}/playback/progress', [ListenEpisodePlaybackController::class, 'progress'])->name('episodes.playback.progress');
    Route::post('/episodes/{episode:episode_key}/playback/complete', [ListenEpisodePlaybackController::class, 'complete'])->name('episodes.playback.complete');
});

Route::middleware(['auth', 'viewer.allowed'])->group(function (): void {
    Route::get('/episodes', static fn () => redirect()->route('listen.episodes.index'))->name('episodes.index');
    Route::get('/episodes/{episode:episode_key}', static fn (string $episode) => redirect()->route('listen.episodes.show', ['episode' => $episode]))->name('episodes.show');
    Route::get('/episodes/{episode:episode_key}/audio', static fn (string $episode) => redirect()->route('listen.episodes.audio', ['episode' => $episode]))->name('episodes.audio');
    Route::get('/episodes/{episode:episode_key}/download', static fn (string $episode) => redirect()->route('listen.episodes.download', ['episode' => $episode]))->name('episodes.download');
});
