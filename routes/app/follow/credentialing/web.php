<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Administration\Files\ViewFileController;
use App\Http\Controllers\Credentialing\MainCredentialingController;
use App\Http\Controllers\Follow\DocumentRequirementController;
use App\Http\Controllers\Administration\Files\ValidateFileController;
use App\Http\Controllers\Administration\Files\UploadFileController;
use App\Http\Controllers\Follow\ProcessUpdateController;
use App\Http\Controllers\Follow\ProcessStatusController;
use App\Http\Controllers\Follow\ResetUuidsController;

/*
|--------------------------------------------------------------------------
| Rutas para visualizar archivos (PROTEGIDA)
|--------------------------------------------------------------------------
|
| Debe exigir sesión (auth) y aceptar nombres con puntos / subcarpetas.
| Se usa el name 'files.cloud.view' porque tu backend y Vue lo referencian así.
*/
Route::middleware(['web', 'auth'])
    ->get('/cloud/view/{filename}', [ViewFileController::class, 'view'])
    ->where('filename', '.*')   // acepta subcarpetas y puntos
    ->name('files.cloud.view');

/*
|--------------------------------------------------------------------------
| Área autenticada
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'role:2'])->group(function () {
    // Proceso principal
    Route::post('/follow/main', [MainCredentialingController::class, 'main'])
        ->name('main');

    // Documentos por puesto
    Route::get(
        '/follow/positions/{positionId}/documents',
        [DocumentRequirementController::class, 'listByPosition']
    )->name('follow.positions.documents');

    // Archivos
    Route::post('/files/validate', [ValidateFileController::class, 'validateFile'])
        ->name('files.validate');

    Route::post('/files/upload', [UploadFileController::class, 'upload'])
        ->name('files.upload');

    // Proceso de actualización
    Route::post('/follow/mark-updated', [ProcessUpdateController::class, 'markUpdated'])
        ->name('follow.markUpdated');

    Route::get('/follow/updated-info', [ProcessUpdateController::class, 'getUpdatedInfo'])
        ->name('follow.updatedInfo');

    // Finalizar envío
    Route::post('/follow/finalize-upload', [ProcessStatusController::class, 'finalizeUpload'])
        ->name('follow.finalizeUpload');

    // Reset de UUIDs
    Route::post('/follow/reset-uuids', [ResetUuidsController::class, 'resetGlobal'])
        ->name('follow.reset-uuids');
});
