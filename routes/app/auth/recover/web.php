<?php

use App\Http\Controllers\Auth\Recover\RecoverPasswordController;
use App\Http\Controllers\Auth\Recover\ViewRecoverUserController;

// Routes configured for application
// get
Route::get('/recover', [ViewRecoverUserController::class, 'recover'])->name('recover');

// post
Route::post('/recover/setPassword', [RecoverPasswordController::class, 'setPassword'])->name('recover.setPassword');

