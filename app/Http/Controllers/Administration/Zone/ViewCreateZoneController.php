<?php

namespace App\Http\Controllers\Administration\Zone;
use App\Http\Controllers\Controller;


class ViewCreateZoneController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('administration.zone.form');
    }
}
