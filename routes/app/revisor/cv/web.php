<?php

use Illuminate\Support\Facades\Route;

// Más adelante podemos agregar middleware ['web', 'auth', 'role:3']
// Por ahora solo diseño.
Route::prefix('revisor')->group(function () {
    Route::view('/empleados', 'revisor.empleados')
        ->name('revisor.empleados');

    Route::view('/empleados/{id}', 'revisor.empleado-show')
        ->name('revisor.empleados.show');
});
