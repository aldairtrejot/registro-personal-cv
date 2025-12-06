<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) cat_puestos
        Schema::create('profesionalizacion.cat_puestos', function (Blueprint $table) {
            $table->increments('id_puesto');
            $table->string('nombre', 200)->unique();
            $table->boolean('activo')->default(true);
        });

        // 2) cat_unidades_adscripcion
        Schema::create('profesionalizacion.cat_unidades_adscripcion', function (Blueprint $table) {
            $table->increments('id_unidad');
            $table->string('nombre', 200);
            $table->string('coordinacion', 200)->nullable();
            $table->boolean('activo')->default(true);

            $table->unique('nombre', 'uq_cat_unidades_nombre');
        });

        // 3) cat_paises
        Schema::create('profesionalizacion.cat_paises', function (Blueprint $table) {
            $table->increments('id_pais');
            $table->string('nombre', 100)->unique();
            $table->boolean('activo')->default(true);
        });

        // 4) cat_nivel_estudios
        Schema::create('profesionalizacion.cat_nivel_estudios', function (Blueprint $table) {
            $table->smallIncrements('id_nivel_estudios');
            $table->smallInteger('codigo')->unique();
            $table->string('descripcion', 100);
            $table->string('descripcion_latin', 100)->nullable();
            $table->boolean('activo')->default(true);
        });

        // 5) cat_carreras
        Schema::create('profesionalizacion.cat_carreras', function (Blueprint $table) {
            $table->increments('id_carrera');
            $table->string('nombre_especifico', 200);
            $table->string('nombre_generico', 200)->nullable();
            $table->string('area', 150)->nullable();
            $table->boolean('activo')->default(true);

            $table->unique('nombre_especifico', 'uq_cat_carreras_especifico');
        });

        // 6) cat_sectores_experiencia
        Schema::create('profesionalizacion.cat_sectores_experiencia', function (Blueprint $table) {
            $table->increments('id_sector');
            $table->string('nombre', 20)->unique(); // Publico / Privado, etc.
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesionalizacion.cat_sectores_experiencia');
        Schema::dropIfExists('profesionalizacion.cat_carreras');
        Schema::dropIfExists('profesionalizacion.cat_nivel_estudios');
        Schema::dropIfExists('profesionalizacion.cat_paises');
        Schema::dropIfExists('profesionalizacion.cat_unidades_adscripcion');
        Schema::dropIfExists('profesionalizacion.cat_puestos');
    }
};
