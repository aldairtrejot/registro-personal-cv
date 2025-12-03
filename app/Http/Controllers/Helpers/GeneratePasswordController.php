<?php

namespace App\Http\Controllers\Helpers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GeneratePasswordController extends Controller
{
    /**
     * The function generates a random password, expecting the total number of characters as a parameter.
     * @param mixed $length
     * @return string
     */
    function setPassword($length = 12)
    {
        $mayusculas = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; // Uppercase letters
        $minusculas = 'abcdefghijklmnopqrstuvwxyz'; // Lowercase letters
        $numeros = '0123456789'; // Numbers
        $simbolos = '@#%*'; // Symbols

        // Ensure the password includes at least one character of each type
        $password = ''; // Initialize empty password string
        $password .= $mayusculas[random_int(0, strlen($mayusculas) - 1)]; // Add a random uppercase letter
        $password .= $minusculas[random_int(0, strlen($minusculas) - 1)]; // Add a random lowercase letter
        $password .= $numeros[random_int(0, strlen($numeros) - 1)]; // Add a random number
        $password .= $simbolos[random_int(0, strlen($simbolos) - 1)]; // Add a random symbol

        // Fill the rest of the password length with random characters from all sets combined
        $todos = $mayusculas . $minusculas . $numeros . $simbolos; // Combine all character sets
        for ($i = 4; $i < $length; $i++) { // Start from 4 because we already added 4 characters
            $password .= $todos[random_int(0, strlen($todos) - 1)]; // Add a random character from combined set
        }

        // Shuffle the password to mix the mandatory characters randomly
        $password = str_shuffle($password); // Randomize character order

        return $password; // Return the generated password
    }
}
