<?php

namespace App\Http\Controllers\Credential;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Validation\Rules\Password;
class UpdateCredentialOnlyUpdateCrontroller extends Controller
{
    public function changePassword(Request $request)
    {

        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);


        $request->merge([
            'newPassword' => $purifier->purify(trim($request->newPassword)),
            'password' => $purifier->purify(trim($request->password)),
        ]);

        $request->validate([
            'password' => [ // Password rules
                'required',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'newPassword' => [ // Password rules
                'required',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Nombre de usuario o contraseña incorrectos.',
        ], 200);
    }
}
