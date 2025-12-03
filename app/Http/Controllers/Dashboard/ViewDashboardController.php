<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ViewDashboardController extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function dashboard()
    {
        return view('dashboard.dashboard');
    }
}
