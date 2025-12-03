<?php

use App\Http\Controllers\Administration\branch\TableBranchController;
use App\Http\Controllers\Administration\branch\ViewBranchController;
use App\Http\Controllers\Administration\Branch\ViewCreateBranchController;
use App\Http\Controllers\Administration\Branch\ViewEditBranchController;
use App\Http\Controllers\Administration\Branch\SaveBranchController;
use App\Http\Controllers\Administration\Branch\MainBranchController;

// Routes configured for application
Route::middleware(['web', 'auth', 'role:1'])->group(function () {
    // get
    Route::get('/role/branch', [ViewBranchController::class, 'branch'])->name('branch');
    Route::get('/role/branch/create', [ViewCreateBranchController::class, 'create'])->name('branch.create');
    Route::get('/role/branch/edit/{id}', [ViewEditBranchController::class, 'edit'])->name('branch.edit');

    // post
    Route::post('/role/branch/table', [TableBranchController::class, 'table'])->name('branch.table');
    Route::post('/role/branch/save', [SaveBranchController::class, 'save'])->name('branch.save');
    Route::post('/role/branch/main', [MainBranchController::class, 'main'])->name('branch.main');
});
