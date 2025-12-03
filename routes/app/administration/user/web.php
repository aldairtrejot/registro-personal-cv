<?php

use App\Http\Controllers\Administration\User\CollectionEntityController;
use App\Http\Controllers\Administration\User\MainUserController;
use App\Http\Controllers\Administration\User\SaveUserController;
use App\Http\Controllers\Administration\User\TableUserController;
use App\Http\Controllers\Administration\User\ViewCreateUserController;
use App\Http\Controllers\Administration\User\ViewEditUserController;
use App\Http\Controllers\Administration\User\ViewUserController;

// Routes configured for application
Route::middleware(['web', 'auth', 'role:1'])->group(function () {
    // get
    Route::get('/user', [ViewUserController::class, 'user'])->name('user');
    Route::get('/user/create', [ViewCreateUserController::class, 'create'])->name('user.create');
    Route::get('/user/edit/{id}', [ViewEditUserController::class, 'edit'])->name('user.edit');

    // post
    Route::post('/user/table', [TableUserController::class, 'table'])->name('user.table');
    Route::post('/user/main', [MainUserController::class, 'main'])->name('user.main');
    Route::post('/user/save', [SaveUserController::class, 'save'])->name('user.save');
    Route::post('/user/collection/entity', [CollectionEntityController::class, 'entity'])->name('user.collection.entity');
});