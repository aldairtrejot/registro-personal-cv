<?php

namespace App\Http\Controllers\Credential;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ViewCredentialOnlyUpdateCrontroller extends Controller
{
    /**
     * The function returns the web view
     * @return \Illuminate\Contracts\View\View
     */
    public function credentialOnlypdate()
    {
        return view('credential.credentialOnlypdate');
    }
}
