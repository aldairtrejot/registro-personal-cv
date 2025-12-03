<?php

use App\Http\Controllers\About\ViewAboutController;

// Routes configured for application
Route::middleware(['web', 'auth', 'role:1,2,3,4,5,6'])->group(function () {
    // get
    Route::get('/about', [ViewAboutController::class, 'about'])->name('about');

    // post
});