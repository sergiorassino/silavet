<?php

namespace App\Support\Autoanalizadores\Drivers;

use App\Support\Autoanalizadores\Contracts\AutoanalizadorDriver;
use RuntimeException;

/**
 * Biosystem A15 — export TXT tabulado (sin encabezado).
 *
 * Columnas 0-based (legado Scriptcase Laboratorio SIV):
 * 0 protocolo, 1 nombre determinación, 3 valor (puede usar coma decimal).
 *
 * El nombre de la determinación es el idAnalizador (misma cadena).
 * Varias filas del mismo protocolo se acumulan; el valor se conserva tal cual
 * (p. ej. "7,7"), como en el legado.
 */
final class BiosystemA15Driver implements AutoanalizadorDriver
{
    private const DELIMITADOR = "\t";

    /**
     * Determinaciones reconocidas (nombre en archivo = idAnalizador).
     * Incluye variantes con espacio final o capitalización distinta del legado.
     *
     * @var list<string>
     */
    private const DETERMINACIONES = [
        'ALBUMIN',
        'ALP-DEA',
        'ALT',
        'AST',
        'BILIRUBIN DIRECT',
        'BILIRUBIN TOTAL',
        'CALCIUM ARSENAZO',
        'CALCIUM MTB',
        'CHOL HDL DIRECT',
        'CHOL LDL DIRECT',
        'CHOLESTEROL',
        'CK',
        'CK-MB',
        'CREATININE ',
        'CREATININE BI',
        'GLUCOSE',
        'G-GT',
        'PHOSPHORUS',
        'PROTEIN TOTAL',
        'PROTEIN URINE',
        'TRIGLYCERIDES',
        'UREA UV',
        'FAS WIENER',
        'GPT WIENER',
        'CK WIENER',
        'GGT WIENER',
        'ggt WIENER',
        'PHOSPHORUS NUEVA',
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

        $permitidas = array_fill_keys(self::DETERMINACIONES, true);
        /** @var array<string, string> $valores */
        $valores = [];

        try {
            while (($datos = fgetcsv($handle, 1000000, self::DELIMITADOR)) !== false) {
                if (! is_array($datos) || $datos === [null] || $datos === false) {
                    continue;
                }

                $idMuestra = trim((string) ($datos[0] ?? ''));
                if ($idMuestra === '' || $idMuestra !== $protocolo) {
                    continue;
                }

                // No trim: el legado compara con nombres que pueden llevar espacio final.
                $det = (string) ($datos[1] ?? '');
                if ($det === '' || ! isset($permitidas[$det])) {
                    continue;
                }

                $valor = trim((string) ($datos[3] ?? ''));
                if ($valor === '' || ! $this->esNumeroAceptable($valor)) {
                    continue;
                }

                $valores[$det] = $valor;
            }
        } finally {
            fclose($handle);
        }

        return $valores === [] ? null : $valores;
    }

    /**
     * Acepta enteros/decimales con punto o coma, y signo opcional.
     */
    private function esNumeroAceptable(string $valor): bool
    {
        $normalizado = str_replace(',', '.', $valor);

        return is_numeric($normalizado);
    }
}
