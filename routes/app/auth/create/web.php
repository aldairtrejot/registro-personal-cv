<?php

use App\Http\Controllers\Auth\Create\CreateUserController;
use App\Http\Controllers\Auth\Create\ViewCreateUserController;

// Routes configured for application
// get
Route::get('/create', [ViewCreateUserController::class, 'create'])->name('create');

// post
Route::post('/create/user', [CreateUserController::class, 'createUser'])->name('create.user');