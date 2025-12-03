<?php

namespace App\Http\Controllers\Auth\Recover;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ViewRecoverUserController extends Controller
{

    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function recover()
    {
        return view('auth.recover');
    }
}
