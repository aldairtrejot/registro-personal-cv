<?php

namespace App\Models\Administration\UserEmployee;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TableUserEmployeeModel extends Model
{
    /**
     * La función devuelve los datos paginados de empleados junto con el total de resultados.
     *
     * @param int $limit
     * @param int $offset
     * @param string|null $search
     * @param int $select
     * @return array{allRow: int, list: \Illuminate\Support\Collection<int, \stdClass>, row: int}
     */
    public function list($limit, $offset, $search, $select)
    {
        // Subconsulta base para contar resultados
        $countQuery = DB::table('profesionalizacion.tbl_empleados')
            ->join('administracion.users', 'profesionalizacion.tbl_empleados.id_tbl_empleados', '=', 'administracion.users.id_tbl_empleado')
            ->where('administracion.users.es_administrador', false);

        $this->applySearch($countQuery, $search);

        $allRow = $countQuery->count();

        // Consulta principal para obtener datos
        $query = DB::table('profesionalizacion.tbl_empleados')
            ->join('administracion.users', 'profesionalizacion.tbl_empleados.id_tbl_empleados', '=', 'administracion.users.id_tbl_empleado')
            ->where('administracion.users.es_administrador', false)
            ->selectRaw("
                profesionalizacion.tbl_empleados.id_tbl_empleados AS id,
                UPPER(
                    profesionalizacion.tbl_empleados.nombre || ' ' || 
                    profesionalizacion.tbl_empleados.primer_apellido || ' ' || 
                    profesionalizacion.tbl_empleados.segundo_apellido
                ) AS nombre,
                administracion.users.email,
                administracion.users.estatus
            ");

        $this->applySearch($query, $search);

        $row = abs(($allRow < ($offset + $select)) ? $allRow : ($offset + $select));

        $list = $query->orderBy('profesionalizacion.tbl_empleados.id_tbl_empleados', 'DESC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'row' => $row,
            'allRow' => $allRow,
            'list' => $list,
        ];
    }

    /**
     * Aplica búsqueda con LIKE insensible a mayúsculas y acentos.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string|null $search
     */
    private function applySearch($query, $search)
    {
        if ($search !== null && trim($search) !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereRaw(
                    "UPPER(TRIM(public.unaccent(
                        profesionalizacion.tbl_empleados.nombre || ' ' || 
                        profesionalizacion.tbl_empleados.primer_apellido || ' ' || 
                        profesionalizacion.tbl_empleados.segundo_apellido
                    ))) LIKE UPPER(TRIM(public.unaccent(?)))",
                    ['%' . $search . '%']
                )->orWhereRaw(
                    "UPPER(TRIM(public.unaccent(administracion.users.email))) LIKE UPPER(TRIM(public.unaccent(?)))",
                    ['%' . $search . '%']
                );
            });
        }
    }
}
