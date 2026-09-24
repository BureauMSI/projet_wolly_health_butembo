<?php

use App\Http\Controllers\Api\SyncController;
use App\Http\Middleware\EnsureSyncToken;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureSyncToken::class)->prefix('sync')->group(function () {
    Route::post('/push', [SyncController::class, 'push'])->name('api.sync.push');
    Route::get('/pull', [SyncController::class, 'pull'])->name('api.sync.pull');
    Route::get('/status', [SyncController::class, 'status'])->name('api.sync.status');
});
