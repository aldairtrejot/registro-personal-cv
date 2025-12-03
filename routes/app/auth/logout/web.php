<?php

use App\Http\Controllers\Auth\Login\LogoutController;

// Routes configured for application
Route::middleware(['web', 'auth', 'role:1,2,3,4,5,6'])->group(function () {
    // get

    // post
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
});
