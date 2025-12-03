<?php

//use App\Http\Controllers\Api\ConnectionC;

use Illuminate\Support\Facades\Route;

/**
 * Configuration for folders to have access to .web
 */
foreach (glob(__DIR__ . '/*/*/*/web.php') as $filename) {
    require $filename;
}


//Route::get('/api/connection', [ConnectionC::class, 'connection'])->name('connection');




