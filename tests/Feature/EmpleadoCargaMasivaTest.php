<?php

namespace Tests\Feature;

use App\Http\Controllers\Cv\EmpleadoCargaMasivaController;
use App\Models\Cv\CvTokenAcceso;
use App\Models\Cv\UsuarioSistema;
use App\Services\Cv\EmpleadoCargaMasivaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmpleadoCargaMasivaTest extends TestCase
{
    private string $temporaryStorage;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'carga_masiva_test',
            'database.connections.carga_masiva_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'logging.default' => 'null',
        ]);
        DB::statement("ATTACH DATABASE ':memory:' AS profesionalizacion");

        Schema::create('profesionalizacion.tbl_empleados', function (Blueprint $table) {
            $table->bigIncrements('id_tbl_empleados');
            $table->char('curp', 18)->unique();
            $table->string('nombre', 150);
            $table->string('primer_apellido', 150);
            $table->string('segundo_apellido', 150)->nullable();
            $table->smallInteger('estatus_cv')->default(0);
            $table->string('puesto_actual', 150)->nullable();
            $table->date('fecha_inicio_puesto')->nullable();
            $table->string('area_adscripcion', 150)->nullable();
        });

        $this->temporaryStorage = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cv-carga-test-' . Str::uuid();
        $this->app->useStoragePath($this->temporaryStorage);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryStorage . '/app/importaciones_empleados/*.json') ?: [] as $file) {
            unlink($file);
        }
        foreach (['/app/importaciones_empleados', '/app', ''] as $suffix) {
            if (is_dir($this->temporaryStorage . $suffix)) {
                rmdir($this->temporaryStorage . $suffix);
            }
        }

        DB::purge('carga_masiva_test');
        parent::tearDown();
    }

    public function test_token_migration_creates_tokens_and_preserves_existing_employees(): void
    {
        $this->insertExistingEmployee();
        $migration = require database_path('migrations/2025_12_03_000001_create_tbl_cv_tokens_acceso.php');
        $migration->up();

        CvTokenAcceso::create([
            'curp' => 'PAAA900101HDFRRN01',
            'correo' => 'prueba@example.test',
            'token' => '123456',
            'expira_en' => now()->addMinutes(15),
        ]);
        $migration->up();

        $this->assertSame(1, CvTokenAcceso::count());
        $this->assertSame(1, DB::table('profesionalizacion.tbl_empleados')->count());

        $migration->down();

        $this->assertFalse(Schema::hasTable('profesionalizacion.tbl_cv_tokens_acceso'));
        $this->assertSame(1, DB::table('profesionalizacion.tbl_empleados')->count());
    }

    public function test_cv_fields_migration_preserves_columns_and_employee_data_on_rollback(): void
    {
        $this->insertExistingEmployee();
        DB::table('profesionalizacion.tbl_empleados')->update(['estatus_cv' => 3, 'puesto_actual' => 'PUESTO DE PRUEBA']);
        $migration = require database_path('migrations/2025_12_03_000005_add_cv_fields_to_tbl_empleados.php');

        $migration->up();
        $migration->up();
        $migration->down();

        $employee = DB::table('profesionalizacion.tbl_empleados')->first();
        $this->assertSame(3, $employee->estatus_cv);
        $this->assertSame('PUESTO DE PRUEBA', $employee->puesto_actual);
        $this->assertTrue(Schema::hasColumns('profesionalizacion.tbl_empleados', [
            'estatus_cv', 'puesto_actual', 'fecha_inicio_puesto', 'area_adscripcion',
        ]));
    }

    public function test_cv_fields_migration_adds_only_missing_columns(): void
    {
        $this->insertExistingEmployee();
        Schema::table('profesionalizacion.tbl_empleados', fn (Blueprint $table) => $table->dropColumn('puesto_actual'));
        $migration = require database_path('migrations/2025_12_03_000005_add_cv_fields_to_tbl_empleados.php');

        $migration->up();

        $this->assertTrue(Schema::hasColumn('profesionalizacion.tbl_empleados', 'puesto_actual'));
        $this->assertSame(1, DB::table('profesionalizacion.tbl_empleados')->count());
    }

    public function test_confirmation_inserts_employee_and_audit_after_audit_migration(): void
    {
        $migration = require database_path('migrations/2026_10_13_000001_create_tbl_empleados_cargas_masivas.php');
        $migration->up();
        $service = $this->serviceWithPreview();
        $user = new UsuarioSistema(['username' => 'prueba']);
        $user->id_usuario = 3;

        $result = $service->confirmar($this->previewPayload()['token'], $user);

        $this->assertTrue($result['confirmado']);
        $this->assertSame(1, $result['resumen']['insertados']);
        $this->assertSame(1, DB::table('profesionalizacion.tbl_empleados')->count());
        $audit = DB::table('profesionalizacion.tbl_empleados_cargas_masivas')->first();
        $this->assertSame(3, $audit->id_usuario);
        $this->assertSame(1, $audit->total_insertados);
        $this->assertSame($result['bitacora_id'], $audit->id_carga_masiva);
    }

    public function test_missing_audit_table_returns_generic_500_and_rolls_back_employees(): void
    {
        $response = app(EmpleadoCargaMasivaController::class)->confirmar(
            $this->confirmationRequest(),
            $this->serviceWithPreview()
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['message' => 'No se pudo confirmar la carga masiva.'], $response->getData(true));
        $this->assertSame(0, DB::table('profesionalizacion.tbl_empleados')->count());
        $this->assertFalse(is_dir($this->temporaryStorage));
    }

    public function test_missing_preview_keeps_404_response(): void
    {
        $response = app(EmpleadoCargaMasivaController::class)->confirmar(
            $this->confirmationRequest(),
            new EmpleadoCargaMasivaService()
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['message' => 'No se encontro la vista previa de la carga.'], $response->getData(true));
    }

    private function insertExistingEmployee(): void
    {
        DB::table('profesionalizacion.tbl_empleados')->insert([
            'curp' => 'PAAA900101HDFRRN01',
            'nombre' => 'PRUEBA',
            'primer_apellido' => 'PRUEBA',
        ]);
    }

    private function serviceWithPreview(): EmpleadoCargaMasivaService
    {
        $service = $this->createPartialMock(EmpleadoCargaMasivaService::class, ['cargarPayload']);
        $service->method('cargarPayload')->willReturn($this->previewPayload());

        return $service;
    }

    private function confirmationRequest(): Request
    {
        return Request::create('/api/revisor/empleados/carga-masiva/confirmar', 'POST', [
            'token' => $this->previewPayload()['token'],
        ]);
    }

    private function previewPayload(): array
    {
        return [
            'token' => '00000000-0000-4000-8000-000000000001',
            'confirmado' => false,
            'archivo_original' => 'prueba.xlsx',
            'archivo_hash' => null,
            'resultados' => [[
                'fila' => 2,
                'nombre' => 'PRUEBA',
                'primer_apellido' => 'PRUEBA',
                'segundo_apellido' => '',
                'curp' => 'PAAA900101HDFRRN01',
                'estado' => 'valido',
                'motivo' => 'Registro nuevo y correcto.',
            ]],
        ];
    }
}
