<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesionalizacion.tbl_cv_experiencia_laboral', function (Blueprint $table) {
            $table->bigIncrements('id_tbl_cv_experiencia_laboral');

            $table->unsignedBigInteger('id_tbl_empleados');

            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();

            // sector de catálogo + texto libre
            $table->unsignedInteger('id_sector')->nullable();
            $table->string('sector', 20)->nullable();

            $table->string('puesto', 150)->nullable();
            $table->string('institucion', 200)->nullable();
            $table->string('campo_experiencia', 100)->nullable();
            $table->smallInteger('orden')->default(1);

            $table->timestampTz('creado_en')->useCurrent();
            $table->timestampTz('actualizado_en')->nullable();

            $table->index(['id_tbl_empleados', 'orden'], 'idx_exp_empleado');

            $table->foreign('id_tbl_empleados')
                ->references('id_tbl_empleados')
                ->on('profesionalizacion.tbl_empleados')
                ->onDelete('cascade');

            $table->foreign('id_sector')
                ->references('id_sector')
                ->on('profesionalizacion.cat_sectores_experiencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionalizacion.tbl_cv_experiencia_laboral');
    }
};
