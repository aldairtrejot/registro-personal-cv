<?php
use Mews\Captcha\Facades\Captcha;

// Routes configured for application

//get
Route::get('/captcha', function () {
    return Captcha::create('_white'); });

//post