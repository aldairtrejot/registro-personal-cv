<?php
// routes/web_employee.php (o donde tengas estas rutas)

use App\Http\Controllers\Administration\Employee\ViewEmployeeController;
use App\Http\Controllers\Administration\Employee\ViewCreateEmployeeController;
use App\Http\Controllers\Administration\Employee\ViewEditEmployeeController;
use App\Http\Controllers\Administration\Employee\TableEmployeeController;
use App\Http\Controllers\Administration\Employee\MainEmployeeController;
use App\Http\Controllers\Administration\Employee\SaveEmployeeController;
use App\Http\Controllers\Administration\Employee\StatusEmployeeController;
use App\Http\Controllers\Administration\Employee\ExportEmployeeController;
use App\Http\Controllers\Administration\Employee\DocumentController;
use App\Http\Controllers\Administration\Files\ViewFileController;
use App\Http\Controllers\Administration\Employee\EmployeeHistoryController;
use App\Http\Controllers\Administration\Employee\EmployeeDocumentHistoryController;
use App\Http\Controllers\Administration\Employee\ProcessStatusController;
use App\Http\Controllers\Administration\Employee\RejectProcessController;
use App\Http\Controllers\Administration\Employee\CedulaValidationController;

Route::middleware(['web', 'auth', 'role:1,2,3,4,5,6'])->group(function () {

    // GET
    Route::get('/employee', [ViewEmployeeController::class, 'employee'])->name('employee');
    Route::get('/employee/create', [ViewCreateEmployeeController::class, 'create'])->name('employee.create');
    Route::get('/employee/edit/{id}', [ViewEditEmployeeController::class, 'edit'])->name('employee.edit');
    Route::get('/employee/export', [ExportEmployeeController::class, 'export'])->name('employee.export');

    Route::get('/employee/{prof_id}/history', [EmployeeHistoryController::class, 'index'])
        ->whereNumber('prof_id')->name('employee.history');
    Route::get('/employee/document/{doc_id}/history', [EmployeeDocumentHistoryController::class, 'index'])
        ->whereNumber('doc_id')->name('employee.document.history');

    
    Route::get('/cloud/view/{filename}', [ViewFileController::class, 'view'])
        ->name('files.cloud.view')->where('filename', '[^/]+');

    // POST
    Route::post('/employee/table', [TableEmployeeController::class, 'table'])->name('employee.table');
    Route::post('/employee/main', [MainEmployeeController::class, 'main'])->name('employee.main');
    Route::post('/employee/save', [SaveEmployeeController::class, 'save'])->name('employee.save');
    Route::post('/employee/collection/status', [StatusEmployeeController::class, 'list'])->name('employee.status.list');

    Route::post('/employee/get', [ViewEditEmployeeController::class, 'get'])->name('employee.get');
    Route::post('/employee/activity', [ViewEditEmployeeController::class, 'activity'])->name('employee.activity');
    Route::post('/employee/documents', [ViewEditEmployeeController::class, 'documents'])->name('employee.documents');

    Route::post('/employee/document/update', [DocumentController::class, 'updateStatus'])->name('employee.document.update');

    Route::post('/follow/status/can-advance', [ProcessStatusController::class, 'canAdvance'])->name('follow.status.canAdvance');
    Route::post('/follow/status/advance', [ProcessStatusController::class, 'advance'])->name('follow.status.advance');

    Route::post('/employee/reject', [RejectProcessController::class, 'reject'])->name('employee.reject');

    // ✅ Verificación de cédula (solo Admin=1, Supervisor=4, DGCES=5)
     Route::post('/employee/cedula/validate', [CedulaValidationController::class, 'store'])
        ->name('employee.cedula.validate');
    
      Route::post('/employee/cedula/delete', [CedulaValidationController::class, 'destroy'])
        ->name('employee.cedula.delete');
});
