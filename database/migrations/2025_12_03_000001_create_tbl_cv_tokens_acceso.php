<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('profesionalizacion.tbl_cv_tokens_acceso')) {
            return;
        }

        Schema::create('profesionalizacion.tbl_cv_tokens_acceso', function (Blueprint $table) {
            $table->bigIncrements('id_tbl_cv_tokens_acceso');
            $table->char('curp', 18);
            $table->string('correo', 150);
            $table->string('token', 10);
            $table->timestampTz('creado_en')->useCurrent();
            $table->timestampTz('expira_en');
            $table->timestampTz('usado_en')->nullable();

            $table->index('curp', 'idx_cv_tokens_curp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionalizacion.tbl_cv_tokens_acceso');
    }
};
