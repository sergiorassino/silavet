<?php

namespace App\Support\Autoanalizadores\Drivers;

use App\Support\Autoanalizadores\Contracts\AutoanalizadorDriver;
use RuntimeException;

/**
 * Edan H30 — export CSV con separador `,` (mismo layout Export* que Geo MC).
 *
 * Columnas 0-based (legado Scriptcase LAM / NeoLab / CIVET):
 * 0 ID mstra., 4 WBC, 6 LYM%, 8 MON%, 10 GRA%, 12 EOS%, 13 RBC, 14 HGB,
 * 15 HCT, 16 MCV, 17 MCH, 18 MCHC, 19 RDW_CV, 21 PLT.
 *
 * Diferenciales en % (no absolutos). Validación: WBC, RBC y PLT numéricos.
 */
final class EdanH30Driver implements AutoanalizadorDriver
{
    private const DELIMITADOR = ',';

    private const COL_ID = 0;

    private const COL_WBC = 4;

    private const COL_RBC = 13;

    private const COL_PLT = 21;

    /** @var array<string, int> */
    private const COLUMNAS = [
        'WBC' => 4,
        'LYM' => 6,
        'MON' => 8,
        'GRA' => 10,
        'EOS' => 12,
        'RBC' => 13,
        'HGB' => 14,
        'HCT' => 15,
        'MCV' => 16,
        'MCH' => 17,
        'MCHC' => 18,
        'RDW' => 19,
        'PLT' => 21,
    ];

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

        try {
            // Encabezado
            fgetcsv($handle, 1000000, self::DELIMITADOR);

            while (($datos = fgetcsv($handle, 1000000, self::DELIMITADOR)) !== false) {
                if (! is_array($datos) || $datos === [null] || $datos === false) {
                    continue;
                }

                $idMuestra = trim((string) ($datos[self::COL_ID] ?? ''));
                if ($idMuestra === '' || $idMuestra !== $protocolo) {
                    continue;
                }

                if (
                    $this->extraerNumero((string) ($datos[self::COL_WBC] ?? '')) === null
                    || $this->extraerNumero((string) ($datos[self::COL_RBC] ?? '')) === null
                    || $this->extraerNumero((string) ($datos[self::COL_PLT] ?? '')) === null
                ) {
                    return [];
                }

                $valores = [];
                foreach (self::COLUMNAS as $codigo => $indice) {
                    $crudo = $this->extraerNumero((string) ($datos[$indice] ?? ''));
                    if ($crudo !== null) {
                        $valores[$codigo] = $crudo;
                    }
                }

                return $valores;
            }
        } finally {
            fclose($handle);
        }

        return null;
    }

    private function extraerNumero(string $celda): ?string
    {
        $celda = trim($celda);
        if ($celda === '' || $celda === '---') {
            return null;
        }

        $normalizado = str_replace(',', '.', $celda);
        if (! is_numeric($normalizado)) {
            return null;
        }

        $num = (float) $normalizado;
        $texto = rtrim(rtrim(sprintf('%.6F', $num), '0'), '.');

        return $texto === '' ? '0' : $texto;
    }
}
