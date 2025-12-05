<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profesionalizacion.tbl_cv_estudios_academicos', function (Blueprint $table) {
            $table->bigIncrements('id_tbl_cv_estudios_academicos');
            $table->unsignedBigInteger('id_tbl_empleados');
            $table->string('institucion', 200)->nullable();
            $table->string('pais', 100)->nullable();
            $table->string('nivel', 100)->nullable();
            $table->string('numero_cedula', 50)->nullable();
            $table->string('carrera_generica', 150)->nullable();
            $table->string('carrera_especifica', 150)->nullable();
            $table->string('area_estudios', 150)->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->nullable();

            $table->index('id_tbl_empleados');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionalizacion.tbl_cv_estudios_academicos');
    }
};
