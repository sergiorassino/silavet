<?php

namespace App\Support\Autoanalizadores\Drivers;

use App\Support\Autoanalizadores\Contracts\AutoanalizadorDriver;
use RuntimeException;

/**
 * Mindray BS-120 — export .SHD / CSV con secciones (Reagent, PatientInfo, Sample, TestDetail).
 *
 * Flujo legado Scriptcase (ALQU):
 * 1) En PatientInfo, Name (col 3) = número de protocolo → acumula ID de paciente del aparato (col 0).
 * 2) En TestDetail, si SampleID (col 4) está entre esos IDs → toma ItemID (col 2) y TestResult (col 12).
 *
 * El redondeo por ItemID lo aplica el perfil del lab (overrides), no este driver.
 */
final class MindrayBs120Driver implements AutoanalizadorDriver
{
    private const DELIMITADOR = ',';

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

        $grupo = '';
        /** @var list<string> $idsPacienteAparato */
        $idsPacienteAparato = [];
        /** @var array<string, string> $valores */
        $valores = [];

        try {
            while (($datos = fgetcsv($handle, 1000000, self::DELIMITADOR)) !== false) {
                if (! is_array($datos) || $datos === [null] || $datos === false) {
                    continue;
                }

                $col0 = (string) ($datos[0] ?? '');
                if ($col0 === '') {
                    continue;
                }

                $prefijo = substr($col0, 0, 6);
                $grupo = match ($prefijo) {
                    'Reagen' => 'Reagent',
                    'Patien' => 'PatientInfo',
                    'Sample' => 'Sample',
                    'TestDe' => 'TestDetail',
                    default => $grupo,
                };

                if ($grupo === 'PatientInfo') {
                    $nombre = trim((string) ($datos[3] ?? ''));
                    if ($nombre !== '' && $nombre === $protocolo) {
                        $id = trim((string) ($datos[0] ?? ''));
                        // Evitar capturar la fila de encabezado "ID,QuestDate,..."
                        if ($id !== '' && $id !== 'ID' && ctype_digit($id)) {
                            $idsPacienteAparato[] = $id;
                        }
                    }

                    continue;
                }

                if ($grupo !== 'TestDetail' || $idsPacienteAparato === []) {
                    continue;
                }

                $sampleId = trim((string) ($datos[4] ?? ''));
                if ($sampleId === '' || ! in_array($sampleId, $idsPacienteAparato, true)) {
                    continue;
                }

                $itemId = trim((string) ($datos[2] ?? ''));
                if ($itemId === '' || $itemId === 'ItemID' || ! ctype_digit($itemId)) {
                    continue;
                }

                $crudo = $this->extraerNumero((string) ($datos[12] ?? ''));
                if ($crudo === null) {
                    continue;
                }

                // Último resultado del mismo ItemID gana (retest / varias entradas).
                $valores[$itemId] = $crudo;
            }
        } finally {
            fclose($handle);
        }

        if ($idsPacienteAparato === []) {
            return null;
        }

        return $valores === [] ? null : $valores;
    }

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

        $num = (float) $normalizado;
        $texto = rtrim(rtrim(sprintf('%.8F', $num), '0'), '.');

        return $texto === '' ? '0' : $texto;
    }
}
