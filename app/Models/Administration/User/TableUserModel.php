<?php

namespace App\Models\Administration\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class TableUserModel extends Model
{
    /**
     * 
     * The function returns the table apart from its query, however it expects the limit, offset, 
     * search and select as parameters, returning the generated query, the total and the iterator.
     * @param mixed $limit
     * @param mixed $offset
     * @param mixed $search
     * @param mixed $selectdad
     * @return array{allRow: int, list: \Illuminate\Support\Collection<int, \stdClass>, row: float|int}
     */
    public function list($limit, $offset, $search, $select)
    {
        // Base query común para ambos: conteo y resultados
        $baseQuery = $this->baseQuery();

        // Clonamos la base para el conteo
        $countQuery = clone $baseQuery;
        $this->applySearch($countQuery, $search);
        $allRow = $countQuery->count();

        // Clonamos para aplicar select y paginación
        $query = clone $baseQuery;
        $query->select([
            'administracion.users.id AS id',
            'administracion.users.name AS name',
            'administracion.users.email AS email',
            'administracion.users.estatus AS status'
        ]);

        $this->applySearch($query, $search);

        // Lógica de paginación segura
        $row = abs(($allRow < ($offset + $select)) ? $allRow : ($offset + $select));

        $list = $query->orderBy('administracion.users.id', 'DESC')
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
     * 
     * The function performs the main query, join, etc.
     * @return \Illuminate\Database\Query\Builder
     */
    private function baseQuery()
    {
        $query = DB::table('administracion.users')
            ->where('administracion.users.es_administrador', '=', true);

        return $query;
    }

    /**
     * Private helper to apply the search filters using unaccent and case-insensitive comparison
     * @param mixed $query
     * @param mixed $search
     */
    private function applySearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->whereRaw(
                "UPPER(TRIM(public.unaccent(administracion.users.name))) LIKE UPPER(TRIM(public.unaccent(?)))",
                ['%' . $search . '%']
            )->orWhereRaw(
                    "UPPER(TRIM(public.unaccent(administracion.users.email))) LIKE UPPER(TRIM(public.unaccent(?)))",
                    ['%' . $search . '%']
                );
        });
    }
}
