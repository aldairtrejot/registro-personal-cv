<?php

namespace App\Http\Controllers\Auth\Create;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ViewCreateUserController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('auth.create');
    }
}
