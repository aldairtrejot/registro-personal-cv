<?php
namespace App\Http\Controllers\Administration\Branch;
use App\Http\Controllers\Controller;

class ViewCreateBranchController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('administration.branch.form');
    }
}