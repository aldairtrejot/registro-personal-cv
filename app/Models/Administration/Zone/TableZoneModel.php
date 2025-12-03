<?php

namespace App\Models\Administration\Zone;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class TableZoneModel extends Model
{
    /**
     *
     * The function returns the table apart from its query, however it expects the limit, offset,
     * search and select as parameters, returning the generated query, the total and the iterator.
     * @param mixed $limit
     * @param mixed $offset
     * @param mixed $search
     * @param mixed $select
     * @return array{allRow: int, list: \Illuminate\Support\Collection<int, \stdClass>, row: float|int}
     */
  public function list($limit, $offset, $search, $select)
{
    // Create a query builder to count the total number of matching rows
    $countQuery = DB::table('catalogo.cat_zona');

    // Apply the search filters to the count query
    $this->applySearch($countQuery, $search);

    // Get the total number of rows that match the search
    $allRow = $countQuery->count();

    // Create a new query builder for fetching data
    $query = DB::table('catalogo.cat_zona')
        ->selectRaw('
            catalogo.cat_zona.id_cat_zona AS id,
            UPPER(catalogo.cat_zona.descripcion) AS nombre,
            catalogo.cat_zona.descripcion AS descripcion,
            catalogo.cat_zona.estatus AS estatus
        ');

    // Apply the same search filters to the data query
    $this->applySearch($query, $search);

    // Determine how many rows to return, ensuring the value is always positive
    $row = abs(($allRow < ($offset + $select)) ? $allRow : ($offset + $select));

    // Fetch the list with ordering, pagination, and limit
    $list = $query->orderBy('catalogo.cat_zona.id_cat_zona', 'DESC')
        ->offset($offset)
        ->limit($limit)
        ->get();

    // Return the result as an array with total rows, selected row count, and the list
    return [
        'row' => $row,
        'allRow' => $allRow,
        'list' => $list,
    ];
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
            "UPPER(TRIM(public.unaccent(catalogo.cat_zona.descripcion))) LIKE UPPER(TRIM(public.unaccent(?)))",
            ['%' . $search . '%']
        )->orWhereRaw(
            "UPPER(TRIM(public.unaccent(catalogo.cat_zona.descripcion))) LIKE UPPER(TRIM(public.unaccent(?)))",
            ['%' . $search . '%']
        );
    });
}
}