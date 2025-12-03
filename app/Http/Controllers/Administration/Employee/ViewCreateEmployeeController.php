<?php

namespace App\Http\Controllers\Administration\Employee;
use App\Http\Controllers\Controller;


class ViewCreateEmployeeController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('administration.employee.form');
    }
}
