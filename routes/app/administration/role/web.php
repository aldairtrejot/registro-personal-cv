<?php

use App\Http\Controllers\Administration\Role\TableRoleController;
use App\Http\Controllers\Administration\Role\ViewRoleController;

// Routes configured for application
Route::middleware(['web', 'auth', 'role:1'])->group(function () {
    // get
    Route::get('/role', [ViewRoleController::class, 'role'])->name('role');

    // post
    Route::post('/role/table', [TableRoleController::class, 'table'])->name('role.table');
});
