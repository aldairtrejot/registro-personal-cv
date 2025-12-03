<?php

namespace App\Models\Administration\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UniqueMailUserModel extends Model
{
    /**
     * The function validates that the email is unique, returns true if the script returns information and false if there is no information, 
     * and also waits for the ID to know if it is added or modified.
     * @param mixed $id
     * @param mixed $mail
     * @return bool
     */
    public function uniqueMail($id, $mail)
    {
        $query = DB::table('administracion.users') // Start query on the 'users' table in 'app' schema
            ->select('administracion.users.id') // Select only the 'id' column
            ->whereRaw('TRIM(UPPER(administracion.users.email)) = TRIM(UPPER(?))', [$mail]); // Compare emails in uppercase and trimmed to ignore case/space differences

        if (!empty($id)) {
            $query->where('administracion.users.id', '<>', $id); // Exclude the current user's ID from the uniqueness check
        }

        return $query->exists(); // Return true if email already exists, otherwise false
    }

}
