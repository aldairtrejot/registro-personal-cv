<?php

use App\Http\Controllers\Administration\UserEmployee\TableUserEmployeeController;
use App\Http\Controllers\Administration\UserEmployee\ViewUserEmployeeController;
use App\Http\Controllers\Administration\UserEmployee\ToogleUserEmployeeStatusController;


// Routes configured for application
Route::middleware(['web', 'auth', 'role:1'])->group(function () {
    // get
    Route::get('/useremployee', [ViewUserEmployeeController::class, 'useremployee'])->name('useremployee');
    

    // post
    Route::post('/useremployee/table', [TableUserEmployeeController::class, 'usertable'])->name('useremployee.table');
    Route::post('/useremployee/toggle-status', [ToogleUserEmployeeStatusController::class, 'toggle']);

});