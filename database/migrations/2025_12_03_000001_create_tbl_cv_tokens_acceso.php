<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblCvTokensAcceso extends Migration
{
    public function up()
    {
        Schema::create('profesionalizacion.tbl_cv_tokens_acceso', function (Blueprint $table) {
            $table->bigIncrements('id_tbl_cv_tokens_acceso');
            $table->string('curp', 18);
            $table->string('correo', 150);
            $table->string('token', 10);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('expira_en');
            $table->timestamp('usado_en')->nullable();

            $table->index(['curp', 'correo']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('profesionalizacion.tbl_cv_tokens_acceso');
    }
}
