<?php

use App\Http\Controllers\Credential\UpdateCredentialOnlyUpdateCrontroller;
use App\Http\Controllers\Credential\ViewCredentialOnlyUpdateCrontroller;

// Routes configured for application
Route::middleware(['web', 'auth', 'role:1,2,3'])->group(function () {
    // get
    Route::get('/credential/update', [ViewCredentialOnlyUpdateCrontroller::class, 'credentialOnlypdate'])->name('credential.update');

    // post
    Route::post('/credential/updateOnlyPassword', [UpdateCredentialOnlyUpdateCrontroller::class, 'changePassword'])->name('credential.updateOnlyPassword');
});