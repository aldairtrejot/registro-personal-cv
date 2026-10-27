<?php

namespace App\Services\Cv;

use App\Exceptions\Cv\CargaMasivaNoEncontradaException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmpleadoCargaMasivaService
{
    private const REQUIRED_FIELDS = [
        'nombre',
        'primer_apellido',
        'segundo_apellido',
        'curp',
    ];

    public function previsualizar(UploadedFile $archivo, $usuario): array
    {
        $token = (string) Str::uuid();
        $lectura = $this->leerArchivo($archivo);
        $resultados = $this->clasificarRegistros($lectura['registros']);

        $payload = [
            'token' => $token,
            'archivo_original' => $archivo->getClientOriginalName(),
            'archivo_hash' => @hash_file('sha256', $archivo->getRealPath()) ?: null,
            'usuario_id' => $usuario?->getAuthIdentifier(),
            'usuario' => $usuario?->username ?? $usuario?->email ?? $usuario?->nombre_completo ?? null,
            'encabezados' => $lectura['encabezados'] ?? [],
            'advertencias' => array_values(array_unique(array_merge(
                $lectura['advertencias'] ?? [],
                $this->advertenciasColumnas($lectura['columnas'] ?? [])
            ))),
            'resultados' => $resultados,
            'confirmado' => false,
            'bitacora_id' => null,
        ];

        $payload['resumen'] = $this->resumen($payload['resultados']);

        $this->guardarPayload($payload);

        return $payload;
    }

    public function confirmar(string $token, $usuario): array
    {
        $payload = $this->cargarPayload($token);

        if (!$payload) {
            throw new CargaMasivaNoEncontradaException();
        }

        if (!empty($payload['confirmado'])) {
            return $payload;
        }

        $resultados = $payload['resultados'] ?? [];
        $validos = array_values(array_filter($resultados, fn ($row) => ($row['estado'] ?? null) === 'valido'));
        $insertados = 0;

        DB::beginTransaction();

        try {
            foreach ($validos as $row) {
                $curp = $row['curp'] ?? '';

                $existe = DB::table('profesionalizacion.tbl_empleados')
                    ->whereRaw('UPPER(TRIM(curp)) = ?', [$curp])
                    ->exists();

                if ($existe) {
                    $resultados = $this->actualizarResultado(
                        $resultados,
                        (int) $row['fila'],
                        'duplicado',
                        'La CURP ya existe en la base de datos.'
                    );
                    continue;
                }

                DB::table('profesionalizacion.tbl_empleados')->insert([
                    'curp' => $curp,
                    'nombre' => $row['nombre'],
                    'primer_apellido' => $row['primer_apellido'],
                    'segundo_apellido' => $row['segundo_apellido'] !== '' ? $row['segundo_apellido'] : null,
                ]);

                $insertados++;
                $resultados = $this->actualizarResultado(
                    $resultados,
                    (int) $row['fila'],
                    'insertado',
                    'Registro insertado correctamente.'
                );
            }

            $payload['resultados'] = $resultados;
            $payload['confirmado'] = true;
            $payload['usuario_id'] = $usuario?->getAuthIdentifier();
            $payload['usuario'] = $usuario?->username ?? $usuario?->email ?? $usuario?->nombre_completo ?? null;
            $payload['resumen'] = $this->resumen($payload['resultados']);
            $payload['resumen']['insertados'] = $insertados;

            $payload['bitacora_id'] = DB::table('profesionalizacion.tbl_empleados_cargas_masivas')->insertGetId([
                'id_usuario' => $payload['usuario_id'],
                'usuario' => $payload['usuario'],
                'archivo_original' => $payload['archivo_original'],
                'archivo_hash' => $payload['archivo_hash'],
                'token' => $payload['token'],
                'total_registros' => $payload['resumen']['total'],
                'total_validos' => $payload['resumen']['validos'],
                'total_duplicados' => $payload['resumen']['duplicados'],
                'total_rechazados' => $payload['resumen']['rechazados'],
                'total_insertados' => $payload['resumen']['insertados'],
                'detalle' => json_encode($payload['resultados'], JSON_UNESCAPED_UNICODE),
                'creado_en' => now(),
            ], 'id_carga_masiva');

            $this->guardarPayload($payload);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $payload;
    }

    public function cargarPayload(string $token): ?array
    {
        $token = $this->normalizarToken($token);
        if ($token === '') {
            return null;
        }

        $path = $this->payloadPath($token);
        if (!is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    private function leerArchivo(UploadedFile $archivo): array
    {
        $reader = IOFactory::createReaderForFile($archivo->getRealPath());
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($archivo->getRealPath());
        $sheet = $spreadsheet->getSheet(0);
        $headerInfo = $this->resolverEncabezados($sheet);

        if (!empty($headerInfo['faltantes'])) {
            throw new \InvalidArgumentException(
                'El archivo no contiene las columnas requeridas: ' . implode(', ', $headerInfo['faltantes']) . '.'
            );
        }

        $highestRow = $sheet->getHighestDataRow();
        $registros = [];

        for ($row = $headerInfo['fila'] + 1; $row <= $highestRow; $row++) {
            $registro = [
                'fila' => $row,
                'nombre' => $this->limpiarTexto($this->valorCelda($sheet, $headerInfo['columnas']['nombre'], $row)),
                'primer_apellido' => $this->limpiarTexto($this->valorCelda($sheet, $headerInfo['columnas']['primer_apellido'], $row)),
                'segundo_apellido' => $this->limpiarTexto($this->valorCelda($sheet, $headerInfo['columnas']['segundo_apellido'], $row)),
                'curp' => $this->normalizarCurp($this->valorCelda($sheet, $headerInfo['columnas']['curp'], $row)),
                'estado' => 'pendiente',
                'motivo' => '',
            ];

            if ($this->filaVacia($registro)) {
                continue;
            }

            $registros[] = $registro;
        }

        if (empty($registros)) {
            throw new \InvalidArgumentException('El archivo no contiene registros para procesar.');
        }

        return [
            'registros' => $registros,
            'encabezados' => $headerInfo['encabezados'],
            'columnas' => $headerInfo['columnas'],
            'advertencias' => $headerInfo['advertencias'],
        ];
    }

    private function clasificarRegistros(array $registros): array
    {
        $curpsParaDb = [];

        foreach ($registros as $i => $registro) {
            $motivos = $this->validarRegistroBasico($registro);

            if (!empty($motivos)) {
                $registros[$i]['estado'] = 'rechazado';
                $registros[$i]['motivo'] = implode(' ', $motivos);
                continue;
            }

            $registros[$i]['estado'] = 'valido';
            $registros[$i]['motivo'] = 'Registro nuevo y correcto.';
            $curpsParaDb[] = $registro['curp'];
        }

        $duplicadasArchivo = $this->curpsDuplicadas($curpsParaDb);
        $existentesDb = $this->curpsExistentes($curpsParaDb);

        foreach ($registros as $i => $registro) {
            if (($registro['estado'] ?? '') !== 'valido') {
                continue;
            }

            if (isset($duplicadasArchivo[$registro['curp']])) {
                $registros[$i]['estado'] = 'duplicado';
                $registros[$i]['motivo'] = 'La CURP esta repetida dentro del archivo.';
                continue;
            }

            if (isset($existentesDb[$registro['curp']])) {
                $registros[$i]['estado'] = 'duplicado';
                $registros[$i]['motivo'] = 'La CURP ya existe en la base de datos.';
            }
        }

        return array_values($registros);
    }

    private function validarRegistroBasico(array $registro): array
    {
        $motivos = [];

        if (($registro['nombre'] ?? '') === '') {
            $motivos[] = 'El nombre es obligatorio.';
        }

        if (($registro['primer_apellido'] ?? '') === '') {
            $motivos[] = 'El primer apellido es obligatorio.';
        }

        if (($registro['curp'] ?? '') === '') {
            $motivos[] = 'La CURP es obligatoria.';
        } elseif (!$this->curpTieneEstructuraValida($registro['curp'])) {
            $motivos[] = 'La CURP no tiene una estructura valida de 18 caracteres.';
        }

        foreach (['nombre' => 'nombre', 'primer_apellido' => 'primer apellido', 'segundo_apellido' => 'segundo apellido'] as $field => $label) {
            $value = (string) ($registro[$field] ?? '');
            if ($value !== '' && !$this->textoNombreValido($value)) {
                $motivos[] = "El {$label} contiene caracteres no validos.";
            }
        }

        return $motivos;
    }

    private function resolverEncabezados(Worksheet $sheet): array
    {
        $highestRow = min(15, $sheet->getHighestDataRow());
        $highestColumn = $sheet->getHighestDataColumn();
        $mejor = [
            'fila' => 1,
            'hits' => 0,
            'encabezados' => [],
            'columnas' => [],
        ];

        for ($row = 1; $row <= $highestRow; $row++) {
            $values = $sheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, true, true)[$row] ?? [];
            $columnas = [];
            $encabezados = [];

            foreach ($values as $col => $value) {
                $label = trim((string) ($value ?? ''));
                if ($label === '') {
                    continue;
                }

                $field = $this->campoDesdeEncabezado($label);
                if ($field && !isset($columnas[$field])) {
                    $columnas[$field] = $col;
                    $encabezados[$field] = $label;
                }
            }

            $hits = count(array_intersect(self::REQUIRED_FIELDS, array_keys($columnas)));
            if ($hits > $mejor['hits']) {
                $mejor = compact('row', 'hits', 'encabezados', 'columnas');
                $mejor['fila'] = $row;
            }
        }

        $faltantes = array_values(array_diff(self::REQUIRED_FIELDS, array_keys($mejor['columnas'] ?? [])));

        return [
            'fila' => $mejor['fila'] ?? 1,
            'encabezados' => $mejor['encabezados'] ?? [],
            'columnas' => $mejor['columnas'] ?? [],
            'faltantes' => $faltantes,
            'advertencias' => [],
        ];
    }

    private function campoDesdeEncabezado(string $header): ?string
    {
        $key = $this->normalizarLlave($header);

        return match ($key) {
            'nombre', 'nombres' => 'nombre',
            'primerapellido', 'apellidopaterno' => 'primer_apellido',
            'segundoapellido', 'apellidomaterno' => 'segundo_apellido',
            'curp' => 'curp',
            default => null,
        };
    }

    private function advertenciasColumnas(array $columnas): array
    {
        if (empty($columnas)) {
            return [];
        }

        $orden = array_map(fn ($field) => $columnas[$field] ?? null, self::REQUIRED_FIELDS);
        $ordenNumerico = array_map(fn ($col) => $col ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($col) : 0, $orden);
        $ordenEsperado = $ordenNumerico;
        sort($ordenEsperado);

        if ($ordenNumerico !== $ordenEsperado) {
            return ['Las columnas requeridas fueron reconocidas, pero no estan en el orden esperado.'];
        }

        return [];
    }

    private function valorCelda(Worksheet $sheet, string $column, int $row): string
    {
        $value = $sheet->getCell("{$column}{$row}")->getFormattedValue();

        return trim((string) ($value ?? ''));
    }

    private function filaVacia(array $registro): bool
    {
        return ($registro['nombre'] ?? '') === ''
            && ($registro['primer_apellido'] ?? '') === ''
            && ($registro['segundo_apellido'] ?? '') === ''
            && ($registro['curp'] ?? '') === '';
    }

    private function limpiarTexto(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return mb_strtoupper($value, 'UTF-8');
    }

    private function normalizarCurp(string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($value)) ?? '');
    }

    private function curpTieneEstructuraValida(string $curp): bool
    {
        return (bool) preg_match(
            '/^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9]\d$/',
            $curp
        );
    }

    private function textoNombreValido(string $value): bool
    {
        return (bool) preg_match("/^[\p{L}\p{M}\s.'-]+$/u", $value);
    }

    private function normalizarLlave(string $value): string
    {
        $value = Str::ascii(mb_strtolower(trim($value), 'UTF-8'));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function curpsDuplicadas(array $curps): array
    {
        $counts = array_count_values($curps);

        return array_filter($counts, fn ($count) => $count > 1);
    }

    private function curpsExistentes(array $curps): array
    {
        $curps = array_values(array_unique(array_filter($curps)));
        if (empty($curps)) {
            return [];
        }

        $existentes = [];
        foreach (array_chunk($curps, 500) as $chunk) {
            $rows = DB::table('profesionalizacion.tbl_empleados')
                ->selectRaw('UPPER(TRIM(curp)) as curp')
                ->whereIn(DB::raw('UPPER(TRIM(curp))'), $chunk)
                ->pluck('curp')
                ->all();

            foreach ($rows as $curp) {
                $existentes[$curp] = true;
            }
        }

        return $existentes;
    }

    private function actualizarResultado(array $resultados, int $fila, string $estado, string $motivo): array
    {
        foreach ($resultados as $i => $row) {
            if ((int) ($row['fila'] ?? 0) === $fila) {
                $resultados[$i]['estado'] = $estado;
                $resultados[$i]['motivo'] = $motivo;
                break;
            }
        }

        return $resultados;
    }

    private function resumen(array $resultados): array
    {
        $resumen = [
            'total' => count($resultados),
            'validos' => 0,
            'duplicados' => 0,
            'rechazados' => 0,
            'insertados' => 0,
        ];

        foreach ($resultados as $row) {
            match ($row['estado'] ?? '') {
                'valido' => $resumen['validos']++,
                'duplicado' => $resumen['duplicados']++,
                'rechazado' => $resumen['rechazados']++,
                'insertado' => $resumen['insertados']++,
                default => null,
            };
        }

        return $resumen;
    }

    private function guardarPayload(array $payload): void
    {
        $dir = storage_path('app/importaciones_empleados');
        File::ensureDirectoryExists($dir, 0775, true);

        file_put_contents(
            $this->payloadPath($payload['token']),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function payloadPath(string $token): string
    {
        return storage_path('app/importaciones_empleados/' . $this->normalizarToken($token) . '.json');
    }

    private function normalizarToken(string $token): string
    {
        return preg_replace('/[^a-f0-9-]/i', '', $token) ?? '';
    }
}
