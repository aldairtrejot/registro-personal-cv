<?php

namespace App\Http\Controllers\Administration\role;

use App\Http\Controllers\Controller;

class ViewRoleController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function role()
    {
        return view('administration.role.list');
    }
}
