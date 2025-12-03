<?php

namespace App\Models\File;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class SaveMassiveFileModel extends Model
{
    public function saveMassiveFile($data)
    {
        DB::table('profesionalizacion.ctrl_documentos_profesionalizacion')
            ->insert($data);
    }
}
