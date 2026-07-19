<?php

namespace App\Support\Autoanalizadores\Drivers;

use App\Support\Autoanalizadores\Contracts\AutoanalizadorDriver;
use RuntimeException;

/**
 * Incaa (bioquímica) — export CSV con separador `,`, sin fila de encabezado.
 *
 * Columnas 0-based (legado Scriptcase):
 * 2 nombre determinación, 8 protocolo / ID muestra, 11 texto resultado.
 *
 * Omite filas de absorbancia (`Abs…`). Extrae el número de `Resultado: …`
 * con el mismo substr + preg_replace del legado.
 * Varias filas del mismo protocolo se acumulan en un mapa idAnalizador => valor.
 */
final class IncaaDriver implements AutoanalizadorDriver
{
    private const DELIMITADOR = ',';

    private const COL_DETERMINACION = 2;

    private const COL_PROTOCOLO = 8;

    private const COL_VALOR = 11;

    /**
     * Nombre en el CSV (UTF-8) => idAnalizador en renglones / itemsinforme.
     *
     * @var array<string, string>
     */
    private const MAPA = [
        'PROTE-BIOS' => 'PROTE_BIOS',
        'ALB-BIOS' => 'ALB_BIOS',
        'BILIR T AA WIENER' => 'BILIR_T_AA_WIENER',
        'BILID AA-WIEN' => 'BILID_AA_WIEN',
        'COLES-BIOS' => 'COLES_BIOS',
        'GLUCO-BIOS' => 'GLUCO_BIOS',
        'Triglicéridos-GT Lab' => 'Trigliceridos_GT_Lab',
        'CALCIO ARS.GT' => 'CALCIO_ARS_GT',
        'Fósforo-GT Lab' => 'Fosforo_GT_Lab',
        'PROTI U/LCR-WIEN' => 'PROTI_U_LCR_WIEN',
        'UREA UV GT LAB' => 'UREA_UV_GT_LAB',
        'CREAT-BIOS' => 'CREAT_BIOS',
        'ALT-BIOS' => 'ALT_BIOS',
        'AST-BIOS' => 'AST_BIOS',
        'FAL-BIOS' => 'FAL_BIOS',
        'Amilasa-GT Lab' => 'Amilasa_GT_Lab',
        'CK-BIOS' => 'CK_BIOS',
        'LIPASA-BIOS' => 'LIPASA_BIOS',
        'GAMMA-BIOS' => 'GAMMA_BIOS',
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

        $valores = [];

        try {
            // Sin omitir primera fila: el export Incaa no trae encabezado.
            while (($datos = fgetcsv($handle, 1000000, self::DELIMITADOR)) !== false) {
                if (! is_array($datos) || $datos === [null] || $datos === false) {
                    continue;
                }

                $idMuestra = trim((string) ($datos[self::COL_PROTOCOLO] ?? ''));
                if ($idMuestra === '' || $idMuestra !== $protocolo) {
                    continue;
                }

                $textoValor = (string) ($datos[self::COL_VALOR] ?? '');
                if (str_starts_with($textoValor, 'Abs')) {
                    continue;
                }

                $det = (string) ($datos[self::COL_DETERMINACION] ?? '');
                $codigo = self::MAPA[$det] ?? null;
                if ($codigo === null) {
                    continue;
                }

                $numero = $this->extraerValorResultado($textoValor);
                if ($numero === null) {
                    continue;
                }

                $valores[$codigo] = $numero;
            }
        } finally {
            fclose($handle);
        }

        return $valores === [] ? null : $valores;
    }

    /**
     * Réplica del legado: substr(11, 9) + dejar solo dígitos, punto y signo inicial.
     */
    private function extraerValorResultado(string $textoValor): ?string
    {
        $trozo = substr($textoValor, 11, 9);
        if ($trozo === false || $trozo === '') {
            return null;
        }

        $limpio = preg_replace('/[^\d.-]|(?!^)-/', '', $trozo);
        if (! is_string($limpio) || $limpio === '' || $limpio === '-' || $limpio === '.') {
            return null;
        }

        if (! is_numeric($limpio)) {
            return null;
        }

        return $limpio;
    }
}
