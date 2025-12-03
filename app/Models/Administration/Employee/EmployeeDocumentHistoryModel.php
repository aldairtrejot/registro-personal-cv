<?php

namespace App\Models\Administration\Employee;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EmployeeDocumentHistoryModel extends Model
{
    public $timestamps = false;

    // Nombres fijos según el diagrama ER
    // Tabla de historial de documentos
    protected string $tableFull  = 'profesionalizacion.ctrl_historia_documentos';
    protected string $tableOnly  = 'ctrl_historia_documentos';

    // FK al documento en la tabla de historial
    protected string $docFkCol   = 'id_ctrl_documentos_profesionalizacion';

    // Catálogo de estatus de documento
    protected string $catTable   = 'catalogo.cat_estatus_documento';
    protected string $catAlias   = 'edoc';
    protected string $catPkCol   = 'id_cat_estatus_documento';
    protected string $catDescCol = 'descripcion';

    /**
     * Lista el historial por ID de documento (docId).
     * Devuelve: id (virtual), estatus, actualizado_en (dd/mm/yyyy hh:mm), observaciones (nullable).
     */
    public function listByDocId(int $docId)
    {
        // ¿Existe la columna "observaciones"?
        $hasObs = DB::table('information_schema.columns')
            ->where('table_schema', 'profesionalizacion')
            ->where('table_name', $this->tableOnly)
            ->where('column_name', 'observaciones')
            ->exists();

        $obsSelect = $hasObs ? 'h.observaciones' : 'NULL::text AS observaciones';

        // Columnas que sabemos que están en ctrl_historia_documentos (ver ER)
        // - id_ctrl_historia_documentos (PK)
        // - actualizado_en (timestamp)
        // - id_cat_estatus_documento (FK a catalogo.cat_estatus_documento)
        // - id_ctrl_documentos_profesionalizacion (FK al documento)
        return DB::table(DB::raw($this->tableFull.' AS h'))
            ->join(DB::raw($this->catTable.' AS '.$this->catAlias), 'h.'.$this->catPkCol, '=', $this->catAlias.'.'.$this->catPkCol)
            ->where('h.'.$this->docFkCol, $docId)
            ->orderBy('h.actualizado_en', 'DESC')
            ->selectRaw("
                row_number() over()::int AS id_ctrl_historia_documentos_profesionalizacion,
                {$this->catAlias}.{$this->catDescCol} AS estatus,
                to_char(h.actualizado_en, 'DD/MM/YYYY HH24:MI') AS actualizado_en,
                $obsSelect
            ")
            ->get();
    }
}
