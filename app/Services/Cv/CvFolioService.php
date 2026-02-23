<?php

namespace App\Services\Cv;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CvFolioService
{
    /**
     * Devuelve folio numérico (solo números).
     * Mantiene nombre/método por compatibilidad con tu sistema.
     */
    public function parseConsecutivo($raw): int
    {
        if ($raw === null) return 0;

        $s = trim((string) $raw);
        if ($s === '') return 0;

        // Solo números
        if (!preg_match('/^[0-9]+$/', $s)) return 0;

        // Maneja ceros a la izquierda
        $n = (int) ltrim($s, '0');
        if ($n > 0) return $n;

        return (int) $s; // "0" o "000"
    }

    /**
     * Asigna folio al aprobar con control y trazabilidad:
     * - Control consecutivo: profesionalizacion.cv_folio_sequences (name='cv')
     * - Trazabilidad: profesionalizacion.cv_folios (unique folio, unique empleado_id)
     * - Compatibilidad: profesionalizacion.tbl_empleados.folio_cv y folio_generado_en
     */
    public function asignarONormalizarAlAprobar(int $empleadoId): int
    {
        $empleadosTable = $this->resolveEmpleadosTable();

        return (int) DB::transaction(function () use ($empleadosTable, $empleadoId) {

            // 1) Bloquear fila del empleado
            $rowEmp = $this->fromTable($empleadosTable)
                ->select(['id_tbl_empleados', 'folio_cv'])
                ->where('id_tbl_empleados', $empleadoId)
                ->lockForUpdate()
                ->first();

            if (!$rowEmp) {
                throw new \RuntimeException("No se encontró el empleado id_tbl_empleados={$empleadoId}.");
            }

            $actual = $this->parseConsecutivo($rowEmp->folio_cv ?? null);

            // 2) Si ya tiene folio numérico, asegurar trazabilidad y regresar
            if ($actual > 0) {
                $this->ensureFolioRow($actual, $empleadoId, 'aprobacion_existente');

                // Limpia folio_cv si venía con ceros/espacios (sin afectar el número)
                $raw = trim((string)($rowEmp->folio_cv ?? ''));
                if ($raw !== (string)$actual) {
                    $this->fromTable($empleadosTable)
                        ->where('id_tbl_empleados', $empleadoId)
                        ->update([
                            'folio_cv' => $actual,
                            'folio_generado_en' => DB::raw("COALESCE(folio_generado_en, NOW())"),
                        ]);
                }

                return $actual;
            }

            // 3) Asegurar que exista fila de secuencia (por si BD quedó a medias)
            $seqRow = DB::table('profesionalizacion.cv_folio_sequences')
                ->where('name', 'cv')
                ->lockForUpdate()
                ->first();

            if (!$seqRow) {
                DB::table('profesionalizacion.cv_folio_sequences')->insert([
                    'name' => 'cv',
                    'current_value' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $seqRow = DB::table('profesionalizacion.cv_folio_sequences')
                    ->where('name', 'cv')
                    ->lockForUpdate()
                    ->first();
            }

            if (!$seqRow) {
                throw new \RuntimeException("No se pudo inicializar la secuencia 'cv' en profesionalizacion.cv_folio_sequences.");
            }

            // 4) Siguiente consecutivo
            $next = ((int)$seqRow->current_value) + 1;

            DB::table('profesionalizacion.cv_folio_sequences')
                ->where('name', 'cv')
                ->update([
                    'current_value' => $next,
                    'updated_at' => now(),
                ]);

            // 5) Registrar trazabilidad
            $this->ensureFolioRow($next, $empleadoId, 'revisor');

            // 6) Guardar en empleados
            $this->fromTable($empleadosTable)
                ->where('id_tbl_empleados', $empleadoId)
                ->update([
                    'folio_cv' => $next,
                    'folio_generado_en' => now(),
                ]);

            return $next;
        });
    }

    private function ensureFolioRow(int $folio, int $empleadoId, ?string $origen): void
    {
        try {
            DB::table('profesionalizacion.cv_folios')->updateOrInsert(
                ['empleado_id' => $empleadoId],
                [
                    'folio' => $folio,
                    'asignado_en' => now(),
                    'origen' => $origen,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // Si UNIQUE(folio) tronó, te damos un error entendible
            throw new \RuntimeException(
                "No se pudo registrar el folio {$folio} para empleado_id={$empleadoId}. " .
                "Es probable que ese folio ya esté asignado a otro empleado. Detalle: " . $e->getMessage()
            );
        }
    }

    // ==========================================================
    // Compatibilidad con tu proyecto (mismos helpers)
    // ==========================================================
    private function resolveEmpleadosTable(): string
    {
        $schemas = ['profesionalizacion', 'public', 'cv', 'administracion'];
        $base = 'tbl_empleados';

        if ($this->tableExists($base)) return $base;

        foreach ($schemas as $sch) {
            $full = "{$sch}.{$base}";
            if ($this->tableExists($full)) return $full;
        }

        throw new \RuntimeException("No se encontró la tabla tbl_empleados. Probé schemas: " . implode(', ', $schemas));
    }

    private function tableExists(string $name): bool
    {
        if (DB::getDriverName() === 'pgsql') {
            $r = DB::selectOne("select to_regclass(?) as reg", [$name]);
            return !empty($r?->reg);
        }

        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            return Schema::hasTable(end($parts));
        }

        return Schema::hasTable($name);
    }

    private function fromTable(string $table)
    {
        if (str_contains($table, '.')) {
            return DB::query()->from(DB::raw($table));
        }
        return DB::table($table);
    }
}