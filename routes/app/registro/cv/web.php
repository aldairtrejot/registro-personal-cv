<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cv\WizardController;
use App\Http\Controllers\Cv\RevisorController;
use App\Http\Controllers\Cv\CatalogosController;
// Wizard
Route::get('/registro-cv', fn () => view('registro.wizard'))
    ->name('registro.wizard');

// Revisor
Route::view('/revisor/empleados', 'revisor.empleados')
    ->name('revisor.empleados');
Route::view('/revisor/empleados/{id}', 'revisor.empleado-show')
    ->name('revisor.empleados.show');

// API WIZARD
Route::prefix('api/cv')->group(function () {
    Route::post('/send-token',       [WizardController::class, 'sendToken']);
    Route::post('/validate-token',   [WizardController::class, 'validateToken']);
    Route::post('/datos-personales', [WizardController::class, 'saveDatosPersonales']);
    Route::post('/experiencias',     [WizardController::class, 'saveExperiencias']);
    Route::post('/estudios',         [WizardController::class, 'saveEstudios']);
    Route::post('/cursos',           [WizardController::class, 'saveCursos']);
});

// API REVISOR
Route::prefix('api/revisor')->group(function () {
    Route::get('/empleados',              [RevisorController::class, 'index']);
    Route::get('/empleados/{id}',         [RevisorController::class, 'show']);
    Route::post('/empleados/{id}/estatus',[RevisorController::class, 'updateStatus']);
});

Route::prefix('cv/catalogos')->group(function () {
    // estudios
    Route::get('/paises',          [CatalogosController::class, 'paises']);
    Route::get('/niveles-estudio', [CatalogosController::class, 'nivelesEstudio']);
    Route::get('/areas-estudio',   [CatalogosController::class, 'areasEstudio']);

    // puestos
    Route::get('/puestos',             [CatalogosController::class, 'puestos']);
    Route::get('/puestos-especificos', [CatalogosController::class, 'puestosEspecificos']);

    // unidades / coordinaciones
    Route::get('/unidades',                        [CatalogosController::class, 'unidades']);
    Route::get('/coordinaciones-por-unidad/{id}',  [CatalogosController::class, 'coordinacionesPorUnidad']);

     Route::get('/catalogos/carreras-especificas', [CatalogosController::class, 'carrerasEspecificas']);
    Route::get('/catalogos/carrera-info/{id_carrera_especifica}', [CatalogosController::class, 'carreraInfoPorEspecifica']);
       Route::get(
        '/areas-estudio-por-carrera/{idEspecifica}/{idGenerica}',
        [CatalogosController::class, 'areasEstudioPorCarrera']
    );
});