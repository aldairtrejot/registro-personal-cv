<?php

namespace App\Http\Controllers\Administration\User;
use App\Http\Controllers\Controller;


class ViewCreateUserController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('administration.user.form');
    }
}
