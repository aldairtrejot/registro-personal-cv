<?php

use Illuminate\Support\Facades\Route;

Route::get('/registro-cv', function () {
    return view('registro.wizard');
})->name('registro.wizard');
