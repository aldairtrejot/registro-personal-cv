<?php

namespace App\Http\Controllers\Auth\Login;

use App\Http\Controllers\Controller;

class ViewLoginController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function Login()
    {
        return view('auth.login');
    }
}
