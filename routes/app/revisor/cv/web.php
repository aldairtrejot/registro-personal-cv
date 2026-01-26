<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\Login\AuthLoginController;
use App\Http\Controllers\Auth\Login\ViewLoginController;

use App\Http\Controllers\Cv\WizardController;
use App\Http\Controllers\Cv\CatalogosController;
use App\Http\Controllers\Cv\RevisorController;
use App\Http\Controllers\Cv\ReporteCvController;
use App\Http\Controllers\Cv\RevisorPdfController;

/*
|-------------------------------------------------------------------------- 
| Auth
|-------------------------------------------------------------------------- 
*/
Route::get('/login', [ViewLoginController::class, 'login'])->name('login');

Route::post('/auth/authentication', [AuthLoginController::class, 'authentication'])
    ->name('auth.authentication');

/*
|-------------------------------------------------------------------------- 
| Registro CV (Empleado)
|-------------------------------------------------------------------------- 
*/
Route::get('/registro-cv', fn () => view('registro.wizard'))
    ->name('registro.wizard');

/*
|-------------------------------------------------------------------------- 
| API Wizard + Catálogos
|-------------------------------------------------------------------------- 
*/
Route::prefix('api/cv')->group(function () {

    // Wizard
    Route::post('/check-correo',     [WizardController::class, 'checkCorreo']); // (si lo usas)
    Route::post('/send-token',       [WizardController::class, 'sendToken']);

    // ✅ ESTA ES LA QUE TE FALTA (alias)
    // - URL:  POST /api/cv/validar-curp
    // - NAME: api/cv/validar-curp  (por si lo llamas con route('api/cv/validar-curp'))
    Route::post('/validar-curp',     [WizardController::class, 'sendToken'])
        ->name('api/cv/validar-curp');

    Route::post('/validate-token',   [WizardController::class, 'validateToken']);
    Route::post('/datos-personales', [WizardController::class, 'saveDatosPersonales']);
    Route::post('/experiencias',     [WizardController::class, 'saveExperiencias']);
    Route::post('/estudios',         [WizardController::class, 'saveEstudios']);
    Route::post('/cursos',           [WizardController::class, 'saveCursos']);

    // Catálogos (los consume tu Wizard)
    Route::get('/catalogos/paises',          [CatalogosController::class, 'paises']);
    Route::get('/catalogos/niveles-estudio', [CatalogosController::class, 'nivelesEstudio']);
    Route::get('/catalogos/areas-estudio',   [CatalogosController::class, 'areasEstudio']);

    Route::get('/catalogos/puestos',         [CatalogosController::class, 'puestos']);
    Route::get('/catalogos/puestos-especificos', [CatalogosController::class, 'puestosEspecificos']);

    Route::get('/catalogos/unidades',        [CatalogosController::class, 'unidades']);
    Route::get('/catalogos/coordinaciones-por-unidad/{id}', [CatalogosController::class, 'coordinacionesPorUnidad']);

    // Carreras (3 combos en cascada)
    Route::get('/catalogos/carreras-especificas', [CatalogosController::class, 'carrerasEspecificas']);
    Route::get('/catalogos/carreras-genericas/{idEspecifica}', [CatalogosController::class, 'carrerasGenericasPorEspecifica']);
    Route::get('/catalogos/areas-estudio-por-carrera/{idEspecifica}/{idGenerica}', [CatalogosController::class, 'areasEstudioPorCarrera']);
});

/*
|-------------------------------------------------------------------------- 
| Revisor (roles 1 y 3)
|-------------------------------------------------------------------------- 
*/
Route::middleware(['auth', 'role:1,3'])->group(function () {

    // Vistas revisor
    Route::prefix('revisor')->group(function () {
        Route::view('/empleados', 'revisor.empleados')->name('revisor.empleados');
        Route::view('/empleados/{id}', 'revisor.empleado-show')->name('revisor.empleados.show');

        // Descargas PDF/ZIP
        Route::get('/empleados/{id}/pdf', [RevisorPdfController::class, 'pdfPorEmpleadoId']);
        Route::get('/pdf/curp/{curp}', [RevisorPdfController::class, 'pdfPorCurp']);
        Route::get('/pdf/aprobados.zip', [RevisorPdfController::class, 'zipAprobados']);
    });

    // API revisor
    Route::prefix('api/revisor')->group(function () {
        Route::get('/empleados',               [RevisorController::class, 'index']);
        Route::get('/empleados/{id}',          [RevisorController::class, 'show']);
        Route::post('/empleados/{id}/estatus', [RevisorController::class, 'updateStatus']);
    });

    // Excel aprobados
    Route::get('/cv/reportes/empleados-terminados', [ReporteCvController::class, 'exportTerminados'])
        ->name('cv.reportes.empleados_terminados');
});
