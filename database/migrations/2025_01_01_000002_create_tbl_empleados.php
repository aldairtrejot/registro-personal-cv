<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesionalizacion.tbl_empleados', function (Blueprint $table) {
            $table->bigIncrements('id_tbl_empleados');

            $table->char('curp', 18)->unique();
            $table->string('nombre', 150);
            $table->string('primer_apellido', 150);
            $table->string('segundo_apellido', 150)->nullable();
            $table->string('correo', 150)->nullable();

            // Catálogos (opcionales)
            $table->unsignedInteger('id_puesto')->nullable();
            $table->unsignedInteger('id_unidad_adscripcion')->nullable();

            // Campos que ya usas
            $table->string('puesto_actual', 150)->nullable();
            $table->date('fecha_inicio_puesto')->nullable();
            $table->string('area_adscripcion', 150)->nullable();

            // 0=Sin capturar, 1=En edición, 2=Enviado, 3=Aprobado, 4=Rechazado
            $table->smallInteger('estatus_cv')->default(0);

            $table->timestampTz('creado_en')->useCurrent();
            $table->timestampTz('actualizado_en')->nullable();

            $table->index('curp', 'idx_empleados_curp');

            $table->foreign('id_puesto')
                ->references('id_puesto')
                ->on('profesionalizacion.cat_puestos');

            $table->foreign('id_unidad_adscripcion')
                ->references('id_unidad')
                ->on('profesionalizacion.cat_unidades_adscripcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionalizacion.tbl_empleados');
    }
};
