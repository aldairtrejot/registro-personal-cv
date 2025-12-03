<?php

use App\Http\Controllers\Dashboard\ViewDashboardController;

// Routes configured for application
Route::middleware(['web', 'auth', 'role:1,2,3,4,5,6'])->group(function () {
    // get
    Route::get('/dashboard', [ViewDashboardController::class, 'dashboard'])->name('dashboard');

    // post

});


