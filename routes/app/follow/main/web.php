<?php

use App\Http\Controllers\Follow\CheckPositionController;
use App\Http\Controllers\Follow\DataFollowController;
use App\Http\Controllers\Follow\HistoryFollowController;
use App\Http\Controllers\Follow\UpdatePositionController;
use App\Http\Controllers\Follow\ViewFollowController;

// Routes configured for application
Route::middleware(['web', 'auth', 'role:2'])->group(function () {
    // get
    Route::get('/follow', [ViewFollowController::class, 'follow'])->name('follow');

    // post
    Route::post('/follow/max/position', [UpdatePositionController::class, 'maxPosition'])->name('follow.max.position');
    Route::post('/follow/check/position', [CheckPositionController::class, 'checkPosition'])->name('follow.check.position');
    Route::post('/follow/history', [HistoryFollowController::class, 'historyFollow'])->name('follow.history');
    Route::post('/follow/data', [DataFollowController::class, 'dataFollow'])->name('follow.data.follow');

});


