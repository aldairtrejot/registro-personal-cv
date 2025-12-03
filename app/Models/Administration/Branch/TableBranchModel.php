<?php

namespace App\Models\Administration\Branch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class TableBranchModel extends Model
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
        // Create a query builder to count the total number of matching users
        $countQuery = DB::table('catalogo.cat_rama');

        // Apply the search filters to the count query
        $this->applySearch($countQuery, $search);

        // Get the total number of rows that match the search
        $allRow = $countQuery->count();

        // Create a new query builder for fetching user data
        $query = DB::table('catalogo.cat_rama') 
            ->selectRaw('
                catalogo.cat_rama.id_cat_rama AS id,
                catalogo.cat_rama.descripcion AS descripcion,
                catalogo.cat_rama.estatus AS estatus
            ');

        // Apply the same search filters to the data query
        $this->applySearch($query, $search);

        // Determine how many rows to return, ensuring the value is always positive
        $row = abs(($allRow < ($offset + $select)) ? $allRow : ($offset + $select));

        // Fetch the list of users with ordering, pagination, and limit
        $list = $query->orderBy('catalogo.cat_rama.id_cat_rama', 'DESC')
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
                "UPPER(TRIM(public.unaccent(catalogo.cat_rama.descripcion))) LIKE UPPER(TRIM(public.unaccent(?)))",
                ['%' . $search . '%']
            )->orWhereRaw(
                    "UPPER(TRIM(public.unaccent(catalogo.cat_rama.descripcion))) LIKE UPPER(TRIM(public.unaccent(?)))",
                    ['%' . $search . '%']
                );
        });
    }
}
