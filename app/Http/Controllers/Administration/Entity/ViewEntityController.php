<?php

namespace App\Http\Controllers\Administration\Entity;

use App\Http\Controllers\Controller;

class ViewEntityController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function entity()
    {
        return view('administration.entity.list');
    }
}