<?php

use App\Http\Controllers\Api\EpisodeUploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:episodes:write'])
    ->post('/episodes', EpisodeUploadController::class)
    ->name('api.episodes.store');
