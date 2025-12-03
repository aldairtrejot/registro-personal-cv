<?php

namespace App\Models\Follow;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentRequirementModel
{
    public function listForCurrentUser(): array
    {
        return DB::table('profesionalizacion.ctrl_documentos_profesionalizacion as c')
            ->join('catalogo.cat_tipo_documento as d', 'c.id_cat_tipo_documento', '=', 'd.id_cat_tipo_documento')
            ->join('profesionalizacion.tbl_profesionalizacion as p', 'c.id_tbl_profesionalizacion', '=', 'p.id_tbl_profesionalizacion')
            ->join('profesionalizacion.tbl_empleados as e', 'p.id_tbl_empleados', '=', 'e.id_tbl_empleados')
            ->join('administracion.users as u', 'e.id_tbl_empleados', '=', 'u.id_tbl_empleado')
            ->where('u.id', Auth::id())
            ->select([
                'c.id_ctrl_documentos_profesionalizacion',
                'c.id_tbl_profesionalizacion',
                'd.id_cat_tipo_documento',
                'd.descripcion',
                'c.uuid',
                'c.id_cat_estatus_documento',
            ])
            ->orderBy('c.id_ctrl_documentos_profesionalizacion', 'asc')
            ->get()
            ->map(fn ($row) => [
                'id_ctrl_documentos_profesionalizacion' => (int) $row->id_ctrl_documentos_profesionalizacion,
                'id_tbl_profesionalizacion'             => (int) $row->id_tbl_profesionalizacion,
                'id_cat_tipo_documento'                 => (int) $row->id_cat_tipo_documento,
                'descripcion'                           => (string) $row->descripcion,
                'uuid'                                  => $row->uuid ? (string) $row->uuid : null,
                'id_cat_estatus_documento'              => is_null($row->id_cat_estatus_documento) ? 0 : (int) $row->id_cat_estatus_documento,
            ])
            ->all();
    }

    /**
     * Limpia UUIDs SOLO de documentos RECHAZADOS (1) de un proceso.
     * Si $onlyWhenProcessStage es null, no filtra por etapa.
     * Recomendado: pasar 1 para que solo limpie cuando el proceso esté en EMPLEADO.
     */
    public function resetUuidsForProcess(int $id_tbl_profesionalizacion, ?int $onlyWhenProcessStage = 1): int
    {
        if ($id_tbl_profesionalizacion <= 0) return 0;

        $sql = <<<SQL
UPDATE profesionalizacion.ctrl_documentos_profesionalizacion AS c
SET uuid = NULL
FROM profesionalizacion.tbl_profesionalizacion AS p
WHERE p.id_tbl_profesionalizacion = c.id_tbl_profesionalizacion
  AND c.id_tbl_profesionalizacion = :id
  AND c.id_cat_estatus_documento = 1
  AND COALESCE(c.uuid, '') <> ''
  AND (:stage::int IS NULL OR p.id_cat_estatus = :stage)
SQL;

        return DB::update($sql, [
            'id'    => $id_tbl_profesionalizacion,
            'stage' => $onlyWhenProcessStage,
        ]);
    }
}
