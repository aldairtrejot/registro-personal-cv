<?php

namespace App\Models\Follow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProcessUpdateModel extends Model
{
    public function touchUpdated(int $idTblProfesionalizacion, int $idUsuario, ?string $timestamp = null): bool
    {
        $ts = $timestamp ?: now(config('app.timezone', 'America/Mexico_City'))->format('Y-m-d H:i:s');

        $affected = DB::table('profesionalizacion.tbl_profesionalizacion')
            ->where('id_tbl_profesionalizacion', $idTblProfesionalizacion)
            ->update([
                'actualizado_en'           => $ts,        // timestamp without time zone
                'id_usuario_actualizacion' => $idUsuario, // user id
            ]);

        return $affected > 0;
    }

    public function getUpdatedInfo(int $idTblProfesionalizacion): ?object
    {
        return DB::table('profesionalizacion.tbl_profesionalizacion as p')
            ->leftJoin('administracion.users as u', 'p.id_usuario_actualizacion', '=', 'u.id')
            ->select(
                'p.id_tbl_profesionalizacion',
                'p.actualizado_en',
                'p.id_usuario_actualizacion',
                DB::raw("COALESCE(u.name, u.email) as usuario_actualizo")
            )
            ->where('p.id_tbl_profesionalizacion', $idTblProfesionalizacion)
            ->first();
    }
}

