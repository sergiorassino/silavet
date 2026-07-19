<?php

namespace App\Support\Autoanalizadores\Drivers;

use App\Support\Autoanalizadores\Contracts\AutoanalizadorDriver;
use RuntimeException;

/**
 * Metrolab CM 250 — export CSV (a menudo con fórmula Excel en la col. de protocolo).
 *
 * Layout legado Scriptcase (LabVet Ciudad):
 * - Col 0: protocolo como `="NNNN"` (o el número plano).
 * - Hasta 20 pares determinación/valor en cols 8/9, 11/12, … 65/66
 *   (cada tres columnas: código, valor, unidad).
 *
 * El redondeo por código (GOTL/GPTL/… enteros con miles, CRELc 1 decimal, etc.)
 * lo aplica el perfil del lab vía overrides.
 *
 * También usado por Wiener CM 160 (mismo layout de pares; cambia el perfil de redondeo).
 */
class MetrolabCm250Driver implements AutoanalizadorDriver
{
    private const DELIMITADOR = ',';

    private const MAX_PARES = 20;

    private const COL_DET_INICIO = 8;

    private const COL_VAL_INICIO = 9;

    private const PASO = 3;

    public function buscarPorProtocolo(string $rutaCsv, string $nombreProtocolo): ?array
    {
        $protocolo = trim($nombreProtocolo);
        if ($protocolo === '' || ! is_readable($rutaCsv)) {
            throw new RuntimeException('No se pudo leer el archivo CSV.');
        }

        $handle = fopen($rutaCsv, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Error al abrir el archivo CSV.');
        }

        // Legado: compara contra ="número" (export Excel).
        $protocoloExcel = '="'.$protocolo.'"';

        try {
            // Sin omitir encabezado: el export no lo trae.
            while (($datos = fgetcsv($handle, 1000000, self::DELIMITADOR)) !== false) {
                if (! is_array($datos) || $datos === [null] || $datos === false) {
                    continue;
                }

                $idFila = trim((string) ($datos[0] ?? ''));
                if ($idFila === '' || ! $this->protocoloCoincide($idFila, $protocolo, $protocoloExcel)) {
                    continue;
                }

                $valores = $this->extraerPares($datos);

                return $valores === [] ? null : $valores;
            }
        } finally {
            fclose($handle);
        }

        return null;
    }

    private function protocoloCoincide(string $idFila, string $protocolo, string $protocoloExcel): bool
    {
        if ($idFila === $protocoloExcel || $idFila === $protocolo) {
            return true;
        }

        // Por si fgetcsv / Excel dejan comillas extra: ="2606681" o =2606681
        if (preg_match('/^="?([^"]+)"?$/', $idFila, $m) === 1) {
            return trim($m[1]) === $protocolo;
        }

        return false;
    }

    /**
     * @param  list<string|null>  $datos
     * @return array<string, string>
     */
    private function extraerPares(array $datos): array
    {
        $valores = [];

        for ($n = 0; $n < self::MAX_PARES; $n++) {
            $idxDet = self::COL_DET_INICIO + ($n * self::PASO);
            $idxVal = self::COL_VAL_INICIO + ($n * self::PASO);

            $det = trim((string) ($datos[$idxDet] ?? ''));
            $val = trim((string) ($datos[$idxVal] ?? ''));

            // Placeholders del legado cuando la celda viene vacía.
            if ($det === '' || $det === 'det'.($n + 1)) {
                continue;
            }
            if ($val === '' || $val === '9999') {
                continue;
            }

            $crudo = $this->normalizarNumero($val);
            if ($crudo === null) {
                continue;
            }

            $valores[$det] = $crudo;
        }

        return $valores;
    }

    private function normalizarNumero(string $celda): ?string
    {
        $normalizado = str_replace(',', '.', $celda);
        if (! is_numeric($normalizado)) {
            return null;
        }

        $num = (float) $normalizado;
        $texto = rtrim(rtrim(sprintf('%.8F', $num), '0'), '.');

        return $texto === '' ? '0' : $texto;
    }
}
