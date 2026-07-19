<?php

namespace App\Support\Autoanalizadores\Drivers;

use App\Support\Autoanalizadores\Contracts\AutoanalizadorDriver;
use RuntimeException;

/**
 * Edan H60 — export CSV con separador `,` y fila de encabezado.
 *
 * Columnas 0-based (legado Scriptcase NeoLab):
 * 2 Equipo, 3 ID Muestra, 5 WBC, 7 NEU%, 9 LYM%, 11 MON%, 13 EOS%, 15 BAS%,
 * 16 RBC, 17 HGB, 18 HCT, 19 MCV, 20 MCH, 21 MCHC, 23 RDW_CV, 24 PLT.
 *
 * Firma de archivo: col. Equipo = "Edan H60".
 * Si el protocolo aparece más de una vez, prevalece la última fila (retest).
 * Fuerza NB = 0 como en el legado.
 */
final class EdanH60Driver implements AutoanalizadorDriver
{
    private const DELIMITADOR = ',';

    private const EQUIPO_ESPERADO = 'Edan H60';

    private const COL_EQUIPO = 2;

    private const COL_ID = 3;

    /** @var array<string, int> */
    private const COLUMNAS = [
        'WBC' => 5,
        'NEU%' => 7,
        'LYM%' => 9,
        'MON%' => 11,
        'EOS%' => 13,
        'BAS%' => 15,
        'RBC' => 16,
        'HGB' => 17,
        'HCT' => 18,
        'MCV' => 19,
        'MCH' => 20,
        'MCHC' => 21,
        'RDW_CV' => 23,
        'PLT' => 24,
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

        /** @var array<string, string>|null $valores */
        $valores = null;

        try {
            fgetcsv($handle, 1000000, self::DELIMITADOR);

            while (($datos = fgetcsv($handle, 1000000, self::DELIMITADOR)) !== false) {
                if (! is_array($datos) || $datos === [null] || $datos === false) {
                    continue;
                }

                $equipo = trim((string) ($datos[self::COL_EQUIPO] ?? ''));
                if ($equipo !== self::EQUIPO_ESPERADO) {
                    return [];
                }

                $idMuestra = trim((string) ($datos[self::COL_ID] ?? ''));
                if ($idMuestra === '' || $idMuestra !== $protocolo) {
                    continue;
                }

                $fila = [];
                foreach (self::COLUMNAS as $codigo => $indice) {
                    $crudo = $this->extraerNumero((string) ($datos[$indice] ?? ''));
                    if ($crudo !== null) {
                        $fila[$codigo] = $crudo;
                    }
                }

                // Legado: fuerza basófilos en banda / NB en 0.
                $fila['NB'] = '0';

                $valores = $fila;
            }
        } finally {
            fclose($handle);
        }

        return $valores;
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
