<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('profesionalizacion.tbl_empleados', function (Blueprint $table) {
            if (!Schema::hasColumn('profesionalizacion.tbl_empleados', 'estatus_cv')) {
                $table->smallInteger('estatus_cv')
                    ->default(0)
                    ->comment('0=Sin capturar, 1=En edición, 2=Enviado, 3=Aprobado, 4=Rechazado');
            }

            if (!Schema::hasColumn('profesionalizacion.tbl_empleados', 'puesto_actual')) {
                $table->string('puesto_actual', 150)->nullable();
            }
            if (!Schema::hasColumn('profesionalizacion.tbl_empleados', 'fecha_inicio_puesto')) {
                $table->date('fecha_inicio_puesto')->nullable();
            }
            if (!Schema::hasColumn('profesionalizacion.tbl_empleados', 'area_adscripcion')) {
                $table->string('area_adscripcion', 150)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Estas columnas tambien pertenecen a la migracion inicial de empleados.
        // Se conservan al revertir esta migracion para no eliminar datos existentes.
    }
};
