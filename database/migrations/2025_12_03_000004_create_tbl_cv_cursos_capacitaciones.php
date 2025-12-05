<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profesionalizacion.tbl_cv_cursos_capacitaciones', function (Blueprint $table) {
            $table->bigIncrements('id_tbl_cv_cursos_capacitaciones');
            $table->unsignedBigInteger('id_tbl_empleados');
            $table->string('periodo', 100)->nullable();
            $table->string('nombre_curso', 200)->nullable();
            $table->string('institucion', 200)->nullable();
            $table->smallInteger('orden')->default(1);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->nullable();

            $table->index('id_tbl_empleados');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionalizacion.tbl_cv_cursos_capacitaciones');
    }
};
