<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cv\WizardController;
use App\Http\Controllers\Cv\RevisorController;
use App\Http\Controllers\Cv\CatalogosController;
use App\Http\Controllers\Cv\ReporteCvController;

// ================== WIZARD ==================
Route::get('/registro-cv', fn() => view('registro.wizard'))
    ->name('registro.wizard');

// ================== API WIZARD ==================
Route::prefix('api/cv')->group(function () {
    Route::post('/check-correo', [WizardController::class, 'checkCorreo']);
    Route::post('/send-token', [WizardController::class, 'sendToken']);
    Route::post('/validate-token', [WizardController::class, 'validateToken']);
    Route::post('/datos-personales', [WizardController::class, 'saveDatosPersonales']);
    Route::post('/experiencias', [WizardController::class, 'saveExperiencias']);
    Route::post('/estudios', [WizardController::class, 'saveEstudios']);
    Route::post('/cursos', [WizardController::class, 'saveCursos']);
});

// ================== API REVISOR ==================
Route::prefix('api/revisor')->group(function () {
    Route::get('/empleados', [RevisorController::class, 'index']);
    Route::get('/empleados/{id}', [RevisorController::class, 'show']);
    Route::post('/empleados/{id}/estatus', [RevisorController::class, 'updateStatus']);
});

// ================== API CATÁLOGOS (LO QUE USA VUE) ==================
Route::prefix('api/cv/catalogos')->group(function () {
    // estudios
    Route::get('/paises', [CatalogosController::class, 'paises']);
    Route::get('/niveles-estudio', [CatalogosController::class, 'nivelesEstudio']);
    Route::get('/areas-estudio', [CatalogosController::class, 'areasEstudio']);

    // puestos
    Route::get('/puestos', [CatalogosController::class, 'puestos']);
    Route::get('/puestos-especificos', [CatalogosController::class, 'puestosEspecificos']);

    // unidades / coordinaciones
    Route::get('/unidades', [CatalogosController::class, 'unidades']);
    Route::get('/coordinaciones-por-unidad/{id}', [CatalogosController::class, 'coordinacionesPorUnidad']);

    // CARRERAS (3 combos en cascada)
    Route::get('/carreras-especificas', [CatalogosController::class, 'carrerasEspecificas']);
    Route::get('/carreras-genericas/{idEspecifica}', [CatalogosController::class, 'carrerasGenericasPorEspecifica']);
    Route::get(
        '/areas-estudio-por-carrera/{idEspecifica}/{idGenerica}',
        [CatalogosController::class, 'areasEstudioPorCarrera']
    );
});

// Ajusta los middlewares a los que ya uses en tu proyecto
Route::middleware(['auth', 'role:1,3'])->group(function () {
    Route::get(
        '/cv/reportes/empleados-terminados',
        [ReporteCvController::class, 'exportTerminados']
    )->name('cv.reportes.empleados_terminados');

});
