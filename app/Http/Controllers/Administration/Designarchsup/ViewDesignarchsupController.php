<?php

namespace App\Http\Controllers\Administration\Designarchsup;

use App\Http\Controllers\Controller;

class ViewDesignarchsupController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function designsup()
    {
        return view('administration.designarchsup.list');
    }
}