<?php

namespace App\Models\Follow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class DataFollowModel extends Model
{
    function dataFollow()
    {
        $query = DB::table('administracion.users')
            ->select(
                'administracion.users.id AS id',
                'profesionalizacion.tbl_empleados.nombre AS nombre',
                DB::raw("profesionalizacion.tbl_empleados.primer_apellido || ' ' || profesionalizacion.tbl_empleados.segundo_apellido AS apellido"),
                'profesionalizacion.tbl_empleados.rfc AS rfc',
                'profesionalizacion.tbl_empleados.curp AS curp',
                'profesionalizacion.tbl_empleados.zona_economica AS zona_economica',
                'profesionalizacion.tbl_plazas.tipo_contratacion',
                'catalogo.cat_rama.descripcion AS cat_rama',
                DB::raw("catalogo.cat_puesto.codigo || ' - ' || catalogo.cat_puesto.descripcion AS ant_puesto"),
                'profesionalizacion.tbl_clues.clave_clues AS clave_clues',
                DB::raw("catalogo.cat_entidad.abrev || ' - ' || catalogo.cat_entidad.descripcion AS entidad"),
                DB::raw("max_puesto.codigo || ' - ' || max_puesto.descripcion AS max_puesto_ob")
            )
            ->join('profesionalizacion.tbl_empleados', 'administracion.users.id_tbl_empleado', '=', 'profesionalizacion.tbl_empleados.id_tbl_empleados')
            ->join('profesionalizacion.tbl_plazas', 'profesionalizacion.tbl_empleados.id_tbl_plazas', '=', 'profesionalizacion.tbl_plazas.id_tbl_plazas')
            ->join('catalogo.cat_rama', 'profesionalizacion.tbl_plazas.id_cat_rama', '=', 'catalogo.cat_rama.id_cat_rama')
            ->join('catalogo.cat_puesto', 'profesionalizacion.tbl_plazas.id_cat_puesto', '=', 'catalogo.cat_puesto.id_cat_puesto')
            ->join('profesionalizacion.tbl_clues', 'profesionalizacion.tbl_plazas.id_tbl_clues', '=', 'profesionalizacion.tbl_clues.id_tbl_clues')
            ->join('catalogo.cat_entidad', 'profesionalizacion.tbl_clues.id_cat_entidad', '=', 'catalogo.cat_entidad.id_cat_entidad')
            ->join('profesionalizacion.tbl_profesionalizacion', 'profesionalizacion.tbl_empleados.id_tbl_empleados', '=', 'profesionalizacion.tbl_profesionalizacion.id_tbl_empleados')
            ->join('catalogo.cat_puesto AS max_puesto', 'profesionalizacion.tbl_profesionalizacion.id_cat_sig_puesto', '=', 'max_puesto.id_cat_puesto')
            ->where('administracion.users.id', Auth::id())
            ->first();

        return $query;
    }
}
