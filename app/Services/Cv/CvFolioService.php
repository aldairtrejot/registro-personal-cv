<?php

namespace App\Services\Cv;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CvFolioService
{
    /**
     * Devuelve el folio consecutivo "limpio" (1,2,3...) a partir de cualquier formato:
     * - "3" -> 3
     * - "2026000003" -> 3
     * - "CV-2026-000003" -> 3
     */
    public function parseConsecutivo($raw): int
    {
        $raw = (string)($raw ?? '');
        $digits = preg_replace('/\D+/', '', $raw);
        $digits = trim($digits);

        if ($digits === '') return 0;

        // Si empieza con año (2000-2099), quita el año (primeros 4) y deja el resto
        if (strlen($digits) >= 5) {
            $year = (int)substr($digits, 0, 4);
            if ($year >= 2000 && $year <= 2099) {
                $rest = ltrim(substr($digits, 4), '0');
                return (int)($rest === '' ? 0 : $rest);
            }
        }

        // Si no trae año, es directo
        return (int)ltrim($digits, '0') ?: (int)$digits;
    }

    /**
     * Se usa al aprobar:
     * - Si folio está vacío -> asigna (maxConsecutivo + 1)
     * - Si folio está en formato viejo (ej 2026000003) -> lo normaliza a 3, si está libre
     * - Si ya está correcto (ej 3) -> lo deja
     */
    public function asignarONormalizarAlAprobar(int $empleadoId): int
    {
        $table = $this->resolveEmpleadosTable();

        return (int) DB::transaction(function () use ($table, $empleadoId) {
            $this->lockFolioSequence();

            // Bloquea fila del empleado
            $row = $this->fromTable($table)
                ->select(['folio_cv'])
                ->where('id_tbl_empleados', $empleadoId)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                throw new \RuntimeException("No se encontró el empleado id_tbl_empleados={$empleadoId}.");
            }

            $actualRaw = $row->folio_cv ?? null;
            $actualConsec = $this->parseConsecutivo($actualRaw);

            // 1) Si NO tiene folio -> asigna siguiente consecutivo
            if ($actualConsec <= 0) {
                $max = $this->getMaxConsecutivo($table);
                $next = $max + 1;

                $this->fromTable($table)
                    ->where('id_tbl_empleados', $empleadoId)
                    ->update(['folio_cv' => $next]);

                return $next;
            }

            // 2) Si ya tiene algo, pero viene "viejo" (ej 2026000003) -> normaliza a 3
            //    Detectamos "viejo" si el raw NO es igual al consecutivo (como string)
            $rawStr = trim((string)$actualRaw);
            if ($rawStr !== '' && $rawStr !== (string)$actualConsec) {
                // Solo normaliza si ese consecutivo no está usado por otro empleado
                if (!$this->existeConsecutivoEnOtroEmpleado($table, $actualConsec, $empleadoId)) {
                    $this->fromTable($table)
                        ->where('id_tbl_empleados', $empleadoId)
                        ->update(['folio_cv' => $actualConsec]);

                    return $actualConsec;
                }

                // Si ya existe en otro, asigna uno nuevo (max+1)
                $max = $this->getMaxConsecutivo($table);
                $next = $max + 1;

                $this->fromTable($table)
                    ->where('id_tbl_empleados', $empleadoId)
                    ->update(['folio_cv' => $next]);

                return $next;
            }

            // 3) Ya estaba bien (ej 3)
            return $actualConsec;
        });
    }

    private function existeConsecutivoEnOtroEmpleado(string $table, int $consec, int $excludeEmpleadoId): bool
    {
        if ($consec <= 0) return false;

        // Compara usando el mismo parseo (en SQL) para cubrir formatos viejos
        if (DB::getDriverName() === 'pgsql') {
            $sql = "
                SELECT 1
                FROM {$table}
                WHERE id_tbl_empleados <> ?
                AND (
                    CASE
                        WHEN folio_cv IS NULL THEN 0
                        ELSE
                            CASE
                                WHEN length(regexp_replace(folio_cv::text, '\\D', '', 'g')) >= 5
                                     AND left(regexp_replace(folio_cv::text, '\\D', '', 'g'), 4)::int BETWEEN 2000 AND 2099
                                THEN COALESCE(NULLIF(ltrim(substring(regexp_replace(folio_cv::text, '\\D', '', 'g') from 5), '0'), '')::int, 0)
                                ELSE COALESCE(NULLIF(ltrim(regexp_replace(folio_cv::text, '\\D', '', 'g'), '0'), '')::int, 0)
                            END
                    END
                ) = ?
                LIMIT 1
            ";

            $r = DB::selectOne($sql, [$excludeEmpleadoId, $consec]);
            return !empty($r);
        }

        // Otros motores: fallback simple (si ya guardas folios limpios, basta)
        return $this->fromTable($table)
            ->where('id_tbl_empleados', '<>', $excludeEmpleadoId)
            ->where('folio_cv', $consec)
            ->exists();
    }

    private function getMaxConsecutivo(string $table): int
    {
        if (DB::getDriverName() === 'pgsql') {
            $sql = "
                SELECT COALESCE(MAX(
                    CASE
                        WHEN folio_cv IS NULL THEN 0
                        ELSE
                            CASE
                                WHEN length(regexp_replace(folio_cv::text, '\\D', '', 'g')) >= 5
                                     AND left(regexp_replace(folio_cv::text, '\\D', '', 'g'), 4)::int BETWEEN 2000 AND 2099
                                THEN COALESCE(NULLIF(ltrim(substring(regexp_replace(folio_cv::text, '\\D', '', 'g') from 5), '0'), '')::int, 0)
                                ELSE COALESCE(NULLIF(ltrim(regexp_replace(folio_cv::text, '\\D', '', 'g'), '0'), '')::int, 0)
                            END
                    END
                ), 0) AS max_folio
                FROM {$table}
            ";

            $r = DB::selectOne($sql);
            return (int)($r->max_folio ?? 0);
        }

        // Otros motores
        $max = $this->fromTable($table)->whereNotNull('folio_cv')->max('folio_cv');
        return (int)($max ?? 0);
    }

    private function lockFolioSequence(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT pg_advisory_xact_lock(923415781)");
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
