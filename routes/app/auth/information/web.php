<?php

use App\Http\Controllers\Auth\Information\ViewInformationController;

// Routes configured for application
// get
Route::get('/information', [ViewInformationController::class, 'information'])->name('information');

// post
