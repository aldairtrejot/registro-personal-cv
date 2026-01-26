<?php

namespace App\Models\Cv;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'profesionalizacion.tbl_empleados';
    protected $primaryKey = 'id_tbl_empleados';
    protected $keyType = 'int';
    public $incrementing = true;

    // Columnas reales en tu tabla
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    public $timestamps = true;

    protected $fillable = [
        'curp',
        'nombre',
        'primer_apellido',
        'segundo_apellido',
        'correo',
        'id_puesto',
        'id_unidad_adscripcion',
        'puesto_actual',
        'fecha_inicio_puesto',
        'area_adscripcion',
        'estatus_cv',
        'folio_cv',
        'folio_generado_en',
    ];

    protected $casts = [
    'fecha_inicio_puesto' => 'date:Y-m-d',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
        'folio_generado_en' => 'datetime',
        'estatus_cv' => 'integer',
        'id_puesto' => 'integer',
        'id_unidad_adscripcion' => 'integer',
    ];

    // Para que salga en JSON automáticamente
    protected $appends = ['puesto_label'];

    // Postgres char(18) rellena con espacios
    public function getCurpAttribute($value)
    {
        return trim((string) $value);
    }

    // Relación con catálogo de puestos
    public function puesto()
    {
        return $this->belongsTo(CatPuesto::class, 'id_puesto', 'id_puesto');
    }

    // Label final del puesto
    public function getPuestoLabelAttribute(): string
    {
        $fromCatalog = $this->puesto?->nombre;
        if (!empty($fromCatalog)) return (string) $fromCatalog;

        $fromText = $this->attributes['puesto_actual'] ?? null;
        if (!empty($fromText)) return (string) $fromText;

        return 'SIN PUESTO';
    }
}
