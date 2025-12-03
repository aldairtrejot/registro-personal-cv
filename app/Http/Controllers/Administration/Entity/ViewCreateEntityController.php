<?php

namespace App\Http\Controllers\Administration\Entity;
use App\Http\Controllers\Controller;


class ViewCreateEntityController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('administration.entity.form');
    }
}