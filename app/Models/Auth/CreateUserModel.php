<?php

namespace App\Models\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CreateUserModel extends Model
{
    public function validateOnlyEmail($email)
    {
        $exists = DB::table('administracion.users')
            ->whereRaw('TRIM(UPPER(public.UNACCENT(email))) = TRIM(UPPER(public.UNACCENT(?)))', [$email])
            ->exists(); // retorna true si encuentra, false si no

        return $exists;
    }

    public function validateOnlyRfc($rfc)
    {
        $exists = DB::table('profesionalizacion.tbl_empleados')
            ->whereRaw("UPPER(TRIM(public.UNACCENT(rfc))) = UPPER(TRIM(public.UNACCENT(?)))", [$rfc])
            ->exists();

        return $exists; // true si hay registro, false si no
    }

    public function twoValidate($email, $rfc)
    {
        return DB::table('administracion.users')
            ->join('profesionalizacion.tbl_empleados', 'administracion.users.id_tbl_empleado', '=', 'profesionalizacion.tbl_empleados.id_tbl_empleados')
            ->whereRaw("TRIM(UPPER(public.UNACCENT(administracion.users.email))) = TRIM(UPPER(public.UNACCENT(?)))", [$email])
            ->whereRaw("TRIM(UPPER(public.UNACCENT(profesionalizacion.tbl_empleados.rfc))) = TRIM(UPPER(public.UNACCENT(?)))", [$rfc])
            ->exists();
    }

    public function twoUniqueRfc($rfc)
    {
        return DB::table('administracion.users')
            ->join('profesionalizacion.tbl_empleados', 'administracion.users.id_tbl_empleado', '=', 'profesionalizacion.tbl_empleados.id_tbl_empleados')
            ->whereRaw("TRIM(UPPER(public.UNACCENT(profesionalizacion.tbl_empleados.rfc))) = TRIM(UPPER(public.UNACCENT(?)))", [$rfc])
            ->exists();
    }


    public function getData($rfc)
    {
        return DB::table('profesionalizacion.tbl_empleados')
            ->select(
                'profesionalizacion.tbl_empleados.id_tbl_empleados AS id',
                'profesionalizacion.tbl_empleados.estatus AS estatus',
                DB::raw("profesionalizacion.tbl_empleados.nombre || ' ' || profesionalizacion.tbl_empleados.primer_apellido || ' ' || profesionalizacion.tbl_empleados.segundo_apellido AS nombre")
            )
            ->whereRaw("UPPER(TRIM(public.UNACCENT(profesionalizacion.tbl_empleados.rfc))) = UPPER(TRIM(public.UNACCENT(?)))", [$rfc])
            ->first(); // usa first() para obtener un solo registro
    }

    public function getDataEmployee($email)
    {
        return DB::table('administracion.users')
            ->whereRaw("TRIM(UPPER(public.UNACCENT(administracion.users.email))) = TRIM(UPPER(public.UNACCENT(?)))", [$email])
            ->first();
    }

    public function getDate($fechaBloqueo, $fechaRegistro)
    {
        return DB::table('catalogo.cat_config')
            ->select([
                'catalogo.cat_config.id_cat_config',
                DB::raw('valor::DATE as fecha')
            ])
            ->where('catalogo.cat_config.estatus', true)
            ->whereIn('catalogo.cat_config.id_cat_config', [$fechaBloqueo, $fechaRegistro])
            ->pluck('fecha', 'id_cat_config')
            ->toArray();
    }

}
