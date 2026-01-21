<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // OJO: ajusta el nombre real de tu tabla si es diferente.
        // En PostgreSQL puedes usar esquema.tabla así:
        Schema::table('profesionalizacion.tbl_empleados', function (Blueprint $table) {
            if (!Schema::hasColumn('profesionalizacion.tbl_empleados', 'folio_cv')) {
                $table->string('folio_cv', 30)->nullable()->unique();
            }
            if (!Schema::hasColumn('profesionalizacion.tbl_empleados', 'folio_generado_en')) {
                $table->timestamp('folio_generado_en')->nullable();
            }
        });
    }

    public function down(): void
    {
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
