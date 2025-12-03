<?php

namespace App\Http\Controllers\Auth\Information;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ViewInformationController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function information()
    {
        return view('auth.information');
    }
}
