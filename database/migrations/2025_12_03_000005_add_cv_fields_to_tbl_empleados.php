<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('profesionalizacion.tbl_empleados', function (Blueprint $table) {
            $table->smallInteger('estatus_cv')
                ->default(0)
                ->comment('0=Sin capturar, 1=En edición, 2=Enviado, 3=Aprobado, 4=Rechazado');

            $table->string('puesto_actual', 150)->nullable();
            $table->date('fecha_inicio_puesto')->nullable();
            $table->string('area_adscripcion', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('profesionalizacion.tbl_empleados', function (Blueprint $table) {
            $table->dropColumn(['estatus_cv', 'puesto_actual', 'fecha_inicio_puesto', 'area_adscripcion']);
        });
    }
};
