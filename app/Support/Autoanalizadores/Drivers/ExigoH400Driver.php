<?php

namespace App\Support\Autoanalizadores\Drivers;

use App\Support\Autoanalizadores\Contracts\AutoanalizadorDriver;
use RuntimeException;

/**
 * Boule Exigo H400 — export CSV con separador `;` (fila HEADER + filas DATA).
 *
 * Columnas usadas (0-based), alineadas al script Scriptcase de ALQU:
 * 3 Sample ID 1, 7 WBC, 21 LYM%, 35 MON%, 49 GRA%, 84 HGB, 105 RBC, 119 HCT, 126 RDW%, 140 PLT.
 *
 * Los diferenciales LYM/MON/GRA se toman del % (no del valor absoluto), como en el legado.
 */
final class ExigoH400Driver implements AutoanalizadorDriver
{
    private const DELIMITADOR = ';';

    /** @var array<string, int> */
    private const COLUMNAS = [
        'WBC' => 7,
        'LYM' => 21,
        'MON' => 35,
        'GRA' => 49,
        'HGB' => 84,
        'RBC' => 105,
        'HCT' => 119,
        'RDW' => 126,
        'PLT' => 140,
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
            // Encabezado HEADER
            fgetcsv($handle, 1000000, self::DELIMITADOR);

            while (($datos = fgetcsv($handle, 1000000, self::DELIMITADOR)) !== false) {
                if (! is_array($datos) || $datos === [null] || $datos === false) {
                    continue;
                }

                $idMuestra = trim((string) ($datos[3] ?? ''));
                if ($idMuestra === '' || $idMuestra !== $protocolo) {
                    continue;
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

    /**
     * Normaliza decimales europeos (coma) a punto. Devuelve null si no hay número.
     */
    private function extraerNumero(string $celda): ?string
    {
        $celda = trim($celda);
        if ($celda === '') {
            return null;
        }

        $normalizado = str_replace(',', '.', $celda);
        if (! is_numeric($normalizado)) {
            return null;
        }

        // Conservar representación limpia sin notación científica.
        $num = (float) $normalizado;
        $texto = rtrim(rtrim(sprintf('%.6F', $num), '0'), '.');

        return $texto === '' ? '0' : $texto;
    }
}
