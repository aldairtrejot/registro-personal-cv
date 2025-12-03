<?php

use App\Http\Controllers\Administration\Zone\ViewZoneController;
use App\Http\Controllers\Administration\Zone\ViewCreateZoneController;
use App\Http\Controllers\Administration\Zone\ViewEditZoneController;
use App\Http\Controllers\Administration\Zone\TableZoneController;
use App\Http\Controllers\Administration\Zone\MainZoneController;
use App\Http\Controllers\Administration\Zone\SaveZoneController;

// Routes configured for application
Route::middleware(['web', 'auth', 'role:1'])->group(function () {
    Route::get('/role/zone', [ViewZoneController::class, 'zone'])->name('zone');
    Route::get('/role/zone/create', [ViewCreateZoneController::class, 'create'])->name('zone.create');
    Route::get('/role/zone/edit/{id}', [ViewEditZoneController::class, 'edit'])->name('zone.edit');

    // post
    Route::post('/role/zone/table', [TableZoneController::class, 'table'])->name('zone.table');
    Route::post('/role/zone/main', [MainZoneController::class, 'main'])->name('zone.main');
    Route::post('/role/zone/save', [SaveZoneController::class, 'save'])->name('zone.save');
                                                                                                                                                                 
});

