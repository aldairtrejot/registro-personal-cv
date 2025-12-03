<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'administracion.rel_users_rol';
    public $timestamps = false;

    protected $fillable = [
        'id_users',
        'id_tbl_roles',
    ];
}
