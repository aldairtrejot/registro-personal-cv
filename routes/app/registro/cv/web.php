<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cv\WizardController;
use App\Http\Controllers\Cv\RevisorController;

/*
|--------------------------------------------------------------------------
| HOME / LOGIN / DASHBOARD
|--------------------------------------------------------------------------
*/

// Raíz del sistema -> Wizard de CV (pantalla de CURP + correo)
Route::get('/', function () {
    return redirect()->route('registro.wizard');
});

// /login tradicional -> también al wizard de CV
Route::get('/login', function () {
    return redirect()->route('registro.wizard');
})->name('login');

// Después de autenticarse (login viejo) manda al módulo de revisor
Route::get('/dashboard', function () {
    return redirect()->route('revisor.empleados');
})->name('dashboard');


/*
|--------------------------------------------------------------------------
| VISTA DEL WIZARD (empleado)
|--------------------------------------------------------------------------
*/

Route::get('/registro-cv', function () {
    return view('registro.wizard');
})->name('registro.wizard');


/*
|--------------------------------------------------------------------------
| VISTAS DEL REVISOR
|--------------------------------------------------------------------------
*/

Route::view('/revisor/empleados', 'revisor.empleados')
    ->name('revisor.empleados');

Route::view('/revisor/empleados/{id}', 'revisor.empleado-show')
    ->name('revisor.empleados.show');


/*
|--------------------------------------------------------------------------
| API WIZARD
|--------------------------------------------------------------------------
*/

Route::prefix('api/cv')->group(function () {
    Route::post('/send-token',       [WizardController::class, 'sendToken']);
    Route::post('/validate-token',   [WizardController::class, 'validateToken']);
    Route::post('/datos-personales', [WizardController::class, 'saveDatosPersonales']);
    Route::post('/experiencias',     [WizardController::class, 'saveExperiencias']);
    Route::post('/estudios',         [WizardController::class, 'saveEstudios']);
    Route::post('/cursos',           [WizardController::class, 'saveCursos']);
});


/*
|--------------------------------------------------------------------------
| API REVISOR
|--------------------------------------------------------------------------
*/

Route::prefix('api/revisor')->group(function () {
    Route::get('/empleados',              [RevisorController::class, 'index']);
    Route::get('/empleados/{id}',         [RevisorController::class, 'show']);
    Route::post('/empleados/{id}/estatus',[RevisorController::class, 'updateStatus']);
});
