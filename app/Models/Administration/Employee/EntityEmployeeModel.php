<?php

namespace App\Models\Administration\Employee;

use Illuminate\Database\Eloquent\Model;

class EntityEmployeeModel extends Model
{
    /** Tabla y llave primaria */
    protected $table = 'profesionalizacion.tbl_empleados';
    protected $primaryKey = 'id_tbl_empleados';

    /** PK autoincremental (int) y sin timestamps */
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    /** Asignación masiva */
    protected $fillable = [
        'rfc',
        'curp',
        'primer_apellido',
        'segundo_apellido',
        'nombre',
        'correo_personal',
        'estatus',
        'id_tbl_plazas',
    ];

    /** Casts */
    protected $casts = [
        'estatus' => 'boolean',
    ];

    /** Accessor: nombre completo */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->primer_apellido} {$this->segundo_apellido} {$this->nombre}");
    }
}
