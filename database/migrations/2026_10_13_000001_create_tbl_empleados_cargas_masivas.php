<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesionalizacion.tbl_empleados_cargas_masivas', function (Blueprint $table) {
            $table->bigIncrements('id_carga_masiva');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('usuario', 180)->nullable();
            $table->string('archivo_original', 255);
            $table->string('archivo_hash', 64)->nullable();
            $table->uuid('token')->nullable();
            $table->unsignedInteger('total_registros')->default(0);
            $table->unsignedInteger('total_validos')->default(0);
            $table->unsignedInteger('total_duplicados')->default(0);
            $table->unsignedInteger('total_rechazados')->default(0);
            $table->unsignedInteger('total_insertados')->default(0);
            $table->json('detalle')->nullable();
            $table->timestampTz('creado_en')->useCurrent();

            $table->index('id_usuario', 'idx_emp_cargas_masivas_usuario');
            $table->index('creado_en', 'idx_emp_cargas_masivas_creado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionalizacion.tbl_empleados_cargas_masivas');
    }
};
