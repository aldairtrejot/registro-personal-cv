<?php

namespace App\Services\Cv;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CvFolioService
{
    public function parseConsecutivo($raw): int
    {
        if ($raw === null) return 0;

        $s = trim((string) $raw);
        if ($s === '') return 0;

        if (!preg_match('/^[0-9]+$/', $s)) return 0;

        $n = (int) ltrim($s, '0');
        if ($n > 0) return $n;

        return (int) $s;
    }

    /**
     * Aprobación: si no tiene folio asigna siguiente consecutivo.
     * Si ya tiene folio numérico, lo respeta y asegura trazabilidad.
     */
    public function asignarONormalizarAlAprobar(int $empleadoId): int
    {
        $empleadosTable = $this->resolveEmpleadosTable();

        return (int) DB::transaction(function () use ($empleadosTable, $empleadoId) {

            $rowEmp = $this->fromTable($empleadosTable)
                ->select(['id_tbl_empleados', 'folio_cv'])
                ->where('id_tbl_empleados', $empleadoId)
                ->lockForUpdate()
                ->first();

            if (!$rowEmp) {
                throw new \RuntimeException("No se encontró el empleado id_tbl_empleados={$empleadoId}.");
            }

            $actual = $this->parseConsecutivo($rowEmp->folio_cv ?? null);

            if ($actual > 0) {
                $this->ensureFolioRow($actual, $empleadoId, 'aprobacion_existente');

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

            // lock secuencia
            $seqRow = DB::table('profesionalizacion.cv_folio_sequences')
                ->where('name', 'cv')
                ->lockForUpdate()
                ->first();

            if (!$seqRow) {
                throw new \RuntimeException("No existe la secuencia 'cv' en profesionalizacion.cv_folio_sequences.");
            }

            $next = ((int)$seqRow->current_value) + 1;

            DB::table('profesionalizacion.cv_folio_sequences')
                ->where('name', 'cv')
                ->update([
                    'current_value' => $next,
                    'updated_at' => now(),
                ]);

            $this->ensureFolioRow($next, $empleadoId, 'revisor');

            $this->fromTable($empleadosTable)
                ->where('id_tbl_empleados', $empleadoId)
                ->update([
                    'folio_cv' => $next,
                    'folio_generado_en' => now(),
                ]);

            return $next;
        });
    }

    /**
     * ✅ NUEVO: Asignación manual / reemplazo de folio
     * - folio debe ser numérico > 0
     * - debe ser único (cv_folios.folio unique)
     * - se registra trazabilidad (origen=manual, asignado_en=now)
     * - si el folio manual es mayor que current_value, se sube la secuencia para evitar colisiones futuras
     */
    public function asignarFolioManual(int $empleadoId, int $folio, string $origen = 'manual'): int
    {
        if ($folio <= 0) {
            throw new \RuntimeException("El folio debe ser mayor a 0.");
        }

        $empleadosTable = $this->resolveEmpleadosTable();

        return (int) DB::transaction(function () use ($empleadosTable, $empleadoId, $folio, $origen) {

            // Bloquear empleado
            $rowEmp = $this->fromTable($empleadosTable)
                ->select(['id_tbl_empleados', 'folio_cv'])
                ->where('id_tbl_empleados', $empleadoId)
                ->lockForUpdate()
                ->first();

            if (!$rowEmp) {
                throw new \RuntimeException("No se encontró el empleado id_tbl_empleados={$empleadoId}.");
            }

            // Verificar si el folio ya está asignado a OTRO empleado
            $existsOther = DB::table('profesionalizacion.cv_folios')
                ->where('folio', $folio)
                ->where('empleado_id', '<>', $empleadoId)
                ->exists();

            if ($existsOther) {
                throw new \RuntimeException("El folio {$folio} ya está asignado a otro empleado.");
            }

            // Guardar/actualizar trazabilidad por empleado
            $this->ensureFolioRow($folio, $empleadoId, $origen);

            // Si folio manual es mayor que la secuencia, subir secuencia
            $seqRow = DB::table('profesionalizacion.cv_folio_sequences')
                ->where('name', 'cv')
                ->lockForUpdate()
                ->first();

            if ($seqRow) {
                $current = (int)$seqRow->current_value;
                if ($folio > $current) {
                    DB::table('profesionalizacion.cv_folio_sequences')
                        ->where('name', 'cv')
                        ->update([
                            'current_value' => $folio,
                            'updated_at' => now(),
                        ]);
                }
            }

            // Actualizar tbl_empleados para compatibilidad
            $this->fromTable($empleadosTable)
                ->where('id_tbl_empleados', $empleadoId)
                ->update([
                    'folio_cv' => $folio,
                    'folio_generado_en' => now(),
                ]);

            return $folio;
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
            throw new \RuntimeException(
                "No se pudo registrar el folio {$folio} para empleado_id={$empleadoId}. Detalle: " . $e->getMessage()
            );
        }
    }

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