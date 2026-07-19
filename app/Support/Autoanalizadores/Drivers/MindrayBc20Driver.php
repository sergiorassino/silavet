<?php

namespace App\Support\Autoanalizadores\Drivers;

use App\Support\Autoanalizadores\Contracts\AutoanalizadorDriver;
use RuntimeException;

/**
 * Mindray BC-20 / BC-20s — export CSV con separador `;`.
 *
 * Columnas usadas (0-based), alineadas al export típico del equipo:
 * 0 muestra, 3 WBC, 7 Gran%, 8 Lym%, 9 Mon%, 10 RBC, 11 HGB, 12 HCT, 16 RDW, 18 PLT.
 */
final class MindrayBc20Driver implements AutoanalizadorDriver
{
    private const DELIMITADOR = ';';

    /** @var array<string, int> */
    private const COLUMNAS = [
        'WBC' => 3,
        'Gran' => 7,
        'Lym' => 8,
        'Mon' => 9,
        'RBC' => 10,
        'HGB' => 11,
        'HCT' => 12,
        'RDW' => 16,
        'PLT' => 18,
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

                $idMuestra = trim((string) ($datos[0] ?? ''));
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

    private function extraerNumero(string $celda): ?string
    {
        if (preg_match('/[\d.]+/', $celda, $m) !== 1) {
            return null;
        }

        return $m[0];
    }
}
