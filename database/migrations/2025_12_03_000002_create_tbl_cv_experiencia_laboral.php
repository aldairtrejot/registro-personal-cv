<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profesionalizacion.tbl_cv_experiencia_laboral', function (Blueprint $table) {
            $table->bigIncrements('id_tbl_cv_experiencia_laboral');
            $table->unsignedBigInteger('id_tbl_empleados');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_termino')->nullable();
            $table->string('sector', 20)->nullable(); // publico | privado
            $table->string('puesto', 150)->nullable();
            $table->string('institucion', 200)->nullable();
            $table->string('campo_experiencia', 100)->nullable();
            $table->smallInteger('orden')->default(1);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->nullable();

            $table->index('id_tbl_empleados');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionalizacion.tbl_cv_experiencia_laboral');
    }
};
