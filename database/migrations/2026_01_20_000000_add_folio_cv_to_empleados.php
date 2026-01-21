<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('profesionalizacion.tbl_empleados', function (Blueprint $table) {
            if (!Schema::hasColumn('profesionalizacion.tbl_empleados', 'folio_cv')) {
                $table->string('folio_cv', 30)->nullable();
            }
            if (!Schema::hasColumn('profesionalizacion.tbl_empleados', 'folio_generado_en')) {
                $table->timestamp('folio_generado_en')->nullable();
            }
        });

        // ✅ El UNIQUE conviene hacerlo con índice (en postgres sobre nullable puede repetirse null sin bronca)
        // Si lo quieres estrictamente unique cuando no sea null:
        // CREATE UNIQUE INDEX ... WHERE folio_cv IS NOT NULL
        // Laravel no lo crea directo con Blueprint -> usamos statement:
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS profesionalizacion_tbl_empleados_folio_cv_unique
            ON profesionalizacion.tbl_empleados (folio_cv)
            WHERE folio_cv IS NOT NULL');
    }

    public function down(): void
    {
        // borra índice parcial
        DB::statement('DROP INDEX IF EXISTS profesionalizacion_tbl_empleados_folio_cv_unique');

        Schema::table('profesionalizacion.tbl_empleados', function (Blueprint $table) {
            if (Schema::hasColumn('profesionalizacion.tbl_empleados', 'folio_cv')) {
                $table->dropColumn('folio_cv');
            }
            if (Schema::hasColumn('profesionalizacion.tbl_empleados', 'folio_generado_en')) {
                $table->dropColumn('folio_generado_en');
            }
        });
    }
};
