<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cv\RevisorController;

Route::middleware(['auth', 'role:3'])->group(function () {

    // VISTAS REVISOR
    Route::prefix('revisor')->group(function () {
        Route::view('/empleados', 'revisor.empleados')
            ->name('revisor.empleados');

        Route::view('/empleados/{id}', 'revisor.empleado-show')
            ->name('revisor.empleados.show');
    });

    // API REVISOR
    Route::prefix('api/revisor')->group(function () {
        Route::get('/empleados',              [RevisorController::class, 'index']);
        Route::get('/empleados/{id}',         [RevisorController::class, 'show']);
        Route::post('/empleados/{id}/estatus',[RevisorController::class, 'updateStatus']);
    });
});
