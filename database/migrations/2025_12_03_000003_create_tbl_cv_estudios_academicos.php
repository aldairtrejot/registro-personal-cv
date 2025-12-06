<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesionalizacion.tbl_cv_estudios_academicos', function (Blueprint $table) {
            $table->bigIncrements('id_tbl_cv_estudios_academicos');

            $table->unsignedBigInteger('id_tbl_empleados');

            $table->string('institucion', 200)->nullable();

            // Catálogos + texto libre
            $table->unsignedInteger('id_pais')->nullable();
            $table->string('pais', 100)->nullable();

            $table->smallInteger('id_nivel_estudios')->nullable();
            $table->string('nivel', 100)->nullable();

            $table->string('numero_cedula', 50)->nullable();

            $table->string('carrera_generica', 150)->nullable();
            $table->string('carrera_especifica', 150)->nullable();
            $table->string('area_estudios', 150)->nullable();

            $table->timestampTz('creado_en')->useCurrent();
            $table->timestampTz('actualizado_en')->nullable();

            $table->index('id_tbl_empleados', 'idx_est_empleado');

            $table->foreign('id_tbl_empleados')
                ->references('id_tbl_empleados')
                ->on('profesionalizacion.tbl_empleados')
                ->onDelete('cascade');

            $table->foreign('id_pais')
                ->references('id_pais')
                ->on('profesionalizacion.cat_paises');

            $table->foreign('id_nivel_estudios')
                ->references('id_nivel_estudios')
                ->on('profesionalizacion.cat_nivel_estudios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionalizacion.tbl_cv_estudios_academicos');
    }
};
