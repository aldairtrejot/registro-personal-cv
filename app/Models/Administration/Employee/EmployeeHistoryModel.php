<?php

namespace App\Models\Administration\Employee;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeHistoryModel extends Model
{
    protected $table = 'profesionalizacion.ctrl_historia_profesionalizacion';
    protected $primaryKey = 'id_ctrl_historia_profesionalizacion';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    /** Historial por id_tbl_profesionalizacion (prof_id) */
    public function listByProfId(int $profId)
    {
        // Trae campos existentes (sin "observaciones")
        $rows = DB::table('profesionalizacion.ctrl_historia_profesionalizacion as h')
            ->join('catalogo.cat_estatus as e', 'h.id_cat_estatus', '=', 'e.id_cat_estatus')
            ->where('h.id_tbl_profesionalizacion', $profId)
            ->orderBy('h.actualizado_en', 'DESC')
            ->select([
                'h.id_ctrl_historia_profesionalizacion',
                'e.descripcion as estatus',
                'h.actualizado_en',
            ])
            ->get();

        // Mapea a la forma que espera el front (con "observaciones" como placeholder)
        return $rows->map(function ($r) {
            return [
                'id_ctrl_historia_profesionalizacion' => $r->id_ctrl_historia_profesionalizacion,
                'estatus'        => $r->estatus ?? '—',
                'observaciones'  => '—', // no existe en la tabla; dejamos guion
                'actualizado_en' => $r->actualizado_en
                    ? Carbon::parse($r->actualizado_en)->format('d/m/Y H:i')
                    : '—',
            ];
        })->values();
    }
}
