<?php

use App\Http\Controllers\Administration\Entity\TableEntityController;
use App\Http\Controllers\Administration\Entity\ViewEntityController;
use App\Http\Controllers\Administration\Entity\ViewCreateEntityController;
use App\Http\Controllers\Administration\Entity\ViewEditEntityController;
use App\Http\Controllers\Administration\Entity\SaveEntityController;
use App\Http\Controllers\Administration\Entity\MainEntityController;

// Routes configured for application
Route::middleware(['web', 'auth', 'role:1'])->group(function () {
    // get
    Route::get('/role/entity', [ViewEntityController::class, 'entity'])->name('entity');
    Route::get('/role/entity/create', [ViewCreateEntityController::class, 'create'])->name('entity.create');
    Route::get('/role/entity/edit/{id}', [ViewEditEntityController::class, 'edit'])->name('entity.edit');

    // post
    Route::post('/role/entity/table', [TableEntityController::class, 'table'])->name('entity.table');
    Route::post('/role/entity/save', [SaveEntityController::class, 'save'])->name('entity.save');
    Route::post('/role/entity/main', [MainEntityController::class, 'main'])->name('entity.main');

});