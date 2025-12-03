<?php

namespace App\Models\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class MassiveFileModel extends Model
{
    public function massiveFile($id)
    {
        return DB::table('catalogo.rel_documento_puesto')
            ->where('id_cat_puesto', $id)
            ->pluck('id_cat_tipo_documento');
    }
}
