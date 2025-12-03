<?php

namespace App\Http\Controllers\Administration\UserEmployee;

use App\Http\Controllers\Controller;

class ViewUserEmployeeController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function useremployee()
    {
        return view('administration.useremployee.list');
    }
}
