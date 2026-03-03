<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\Login\LogoutController;

// ✅ Logout: SOLO requiere auth
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
});

// Routes configured for application
Route::middleware(['web', 'auth', 'role:1,2,3,4,5,6'])->group(function () {
    // ... tus demás rutas
});