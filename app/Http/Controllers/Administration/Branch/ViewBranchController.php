<?php

namespace App\Http\Controllers\Administration\Branch;

use App\Http\Controllers\Controller;

class ViewBranchController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function Branch()
    {
        return view('administration.Branch.list');
    }
}
