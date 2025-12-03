<?php

namespace App\Models\Administration\Entity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TableEntityModel extends Model
{
    /**
     * @return array{allRow:int,list:\Illuminate\Support\Collection,row:int}
     */
    public function list($limit, $offset, $search, $select)
    {
        $limit  = max(0, (int) $limit);
        $offset = max(0, (int) $offset);
        $search = (string) ($search ?? '');

        // COUNT
        $countQuery = DB::table('catalogo.cat_entidad');
        $this->applySearch($countQuery, $search);
        $allRow = (int) $countQuery->count();

        // DATA
        $query = DB::table('catalogo.cat_entidad')
            ->selectRaw('
                catalogo.cat_entidad.id_cat_entidad AS id,
                catalogo.cat_entidad.abrev,
                catalogo.cat_entidad.descripcion,
                catalogo.cat_entidad.clave_entidad,
                catalogo.cat_entidad.estatus
            ');

        $this->applySearch($query, $search);

        $list = $query->orderBy('catalogo.cat_entidad.id_cat_entidad', 'DESC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $row = $allRow === 0 ? 0 : min($offset + $limit, $allRow);

        return [
            'row'    => $row,
            'allRow' => $allRow,
            'list'   => $list,
        ];
    }

    private function applySearch($query, $search)
    {
        if (trim($search) === '') return $query;

        $like = '%' . $search . '%';

        return $query->where(function ($q) use ($like) {
            $q->whereRaw(
                "UPPER(TRIM(public.unaccent(catalogo.cat_entidad.descripcion))) LIKE UPPER(TRIM(public.unaccent(?)))",
                [$like]
            )
            ->orWhereRaw(
                "UPPER(TRIM(public.unaccent(catalogo.cat_entidad.clave_entidad))) LIKE UPPER(TRIM(public.unaccent(?)))",
                [$like]
            )
            ->orWhereRaw(
                "UPPER(TRIM(public.unaccent(CAST(catalogo.cat_entidad.abrev AS TEXT)))) LIKE UPPER(TRIM(public.unaccent(?)))",
                [$like]
            );
        });
    }
}
