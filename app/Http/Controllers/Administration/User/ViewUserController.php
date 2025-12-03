<?php

namespace App\Http\Controllers\Administration\User;

use App\Http\Controllers\Controller;

class ViewUserController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function user()
    {
        return view('administration.user.list');
    }
}
