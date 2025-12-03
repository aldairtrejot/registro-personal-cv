<?php

use App\Http\Controllers\Auth\Login\AuthLoginController;
use App\Http\Controllers\Auth\Login\ViewLoginController;

// Routes configured for application
// get
Route::get('/login', [ViewLoginController::class, 'login'])->name('login');

// post
Route::post('/auth/authentication', [AuthLoginController::class, 'authentication'])->name('auth.authentication');