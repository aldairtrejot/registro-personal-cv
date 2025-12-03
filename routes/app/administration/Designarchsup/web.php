<?php
use App\Http\Controllers\Administration\Designarchsup\ViewDesignarchsupController;

// Routes configured for application
Route::middleware(['web', 'auth', 'role:1'])->group(function () {
    // get
    Route::get('/disenoarchsup', [ViewDesignarchsupController::class, 'designsup'])->name('designsup');


});