<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cv\WizardController;
use App\Http\Controllers\Cv\RevisorController;
use App\Http\Controllers\Cv\CatalogosController;
use App\Http\Controllers\Auth\Login\AuthLoginController;
use App\Http\Controllers\Auth\Login\ViewLoginController;
use App\Http\Controllers\Cv\RevisorPdfController;
use App\Http\Controllers\Cv\CvPdfController;

/*
|--------------------------------------------------------------------------
| Rutas de autenticación
|--------------------------------------------------------------------------
*/

Route::get('/login', [ViewLoginController::class, 'login'])->name('login');

Route::post('/auth/authentication', [AuthLoginController::class, 'authentication'])
    ->name('auth.authentication');

/*
|--------------------------------------------------------------------------
| Registro de CV (empleado)
|--------------------------------------------------------------------------
*/

// Vista del wizard
Route::get('/registro-cv', fn () => view('registro.wizard'))
    ->name('registro.wizard');

// API Wizard + Catálogos (prefijo /api/cv)
Route::prefix('api/cv')->group(function () {
    // Wizard
    Route::post('/send-token',       [WizardController::class, 'sendToken']);
    Route::post('/validate-token',   [WizardController::class, 'validateToken']);
    Route::post('/datos-personales', [WizardController::class, 'saveDatosPersonales']);
    Route::post('/experiencias',     [WizardController::class, 'saveExperiencias']);
    Route::post('/estudios',         [WizardController::class, 'saveEstudios']);
    Route::post('/cursos',           [WizardController::class, 'saveCursos']);

    // Catálogos
    Route::get('/catalogos/paises',          [CatalogosController::class, 'paises']);
    Route::get('/catalogos/niveles-estudio', [CatalogosController::class, 'nivelesEstudio']);
    Route::get('/catalogos/areas-estudio',   [CatalogosController::class, 'areasEstudio']);
    Route::get('/catalogos/puestos',         [CatalogosController::class, 'puestos']);
    Route::get('/catalogos/puestos-especificos', [CatalogosController::class, 'puestosEspecificos']);
    Route::get('/catalogos/unidades',        [CatalogosController::class, 'unidades']);
    Route::get('/catalogos/coordinaciones-por-unidad/{id_unidad}', [CatalogosController::class, 'coordinacionesPorUnidad']);
});

/*
|--------------------------------------------------------------------------
| Revisor (protegido con auth + role:3)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:3'])->group(function () {

    // Vistas
    Route::prefix('revisor')->group(function () {
        Route::view('/empleados', 'revisor.empleados')
            ->name('revisor.empleados');

        Route::view('/empleados/{id}', 'revisor.empleado-show')
            ->name('revisor.empleados.show');
    });

    // API
    Route::prefix('api/revisor')->group(function () {
        Route::get('/empleados',              [RevisorController::class, 'index']);
        Route::get('/empleados/{id}',         [RevisorController::class, 'show']);
        Route::post('/empleados/{id}/estatus',[RevisorController::class, 'updateStatus']);
    });
});

// Revisor (protegido con auth + role:1 → ADMIN)
Route::middleware(['auth', 'role:1,3'])->group(function () {
    Route::prefix('revisor')->group(function () {
        Route::view('/empleados', 'revisor.empleados')->name('revisor.empleados');
        Route::view('/empleados/{id}', 'revisor.empleado-show')->name('revisor.empleados.show');
    });

    Route::prefix('api/revisor')->group(function () {
        Route::get('/empleados',              [RevisorController::class, 'index']);
        Route::get('/empleados/{id}',         [RevisorController::class, 'show']);
        Route::post('/empleados/{id}/estatus',[RevisorController::class, 'updateStatus']);
    });

    Route::prefix('revisor')->group(function () {
    Route::get('empleados/{id}/pdf', [RevisorPdfController::class, 'pdfPorEmpleadoId']);
    Route::get('empleados/pdf/{curp}', [RevisorPdfController::class, 'pdfPorCurp']);
    Route::get('empleados/aprobados/zip', [RevisorPdfController::class, 'zipAprobados']);
});
    // nuevos (PDF/ZIP)
    Route::get('empleados/{id}/pdf', [RevisorPdfController::class, 'pdfPorEmpleadoId']);
    Route::get('empleados/pdf/{curp}', [RevisorPdfController::class, 'pdfPorCurp']);
    Route::get('empleados/aprobados/zip', [RevisorPdfController::class, 'zipAprobados']);

    Route::prefix('revisor')->group(function () {
    Route::get('empleados/{id}/pdf', [CvPdfController::class, 'pdfPorEmpleado']);
    Route::get('pdf/curp/{curp}', [CvPdfController::class, 'pdfPorCurp']);
    Route::get('pdf/aprobados.zip', [CvPdfController::class, 'zipAprobados']);
});

});
