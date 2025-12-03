<?php

namespace App\Http\Controllers\Administration\Zone;

use App\Http\Controllers\Controller;

class ViewZoneController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function zone()
    {
        return view('administration.zone.list');
    }
}
