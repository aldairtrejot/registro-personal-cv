<?php

namespace App\Http\Controllers\Administration\Employee;

use App\Http\Controllers\Controller;

class ViewEmployeeController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function employee()
    {
        return view('administration.employee.list');
    }
}
