<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administration\Designarch\ViewDesignarchController;

/*
|--------------------------------------------------------------------------
| Rutas para Diseño de Archivo
|--------------------------------------------------------------------------
| Protegidas por autenticación y rol 1.
*/
Route::middleware(['web', 'auth', 'role:1'])->group(function () {

    // Vista principal
    Route::get('/disenoarch', [ViewDesignarchController::class, 'design'])
        ->name('design');

    // Recepción de archivos (POST)
    Route::post('/disenoarch/upload', [ViewDesignarchController::class, 'upload'])
        ->name('design.upload');
});
