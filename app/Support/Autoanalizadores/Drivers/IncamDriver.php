<?php

namespace App\Support\Autoanalizadores\Drivers;

use App\Support\Autoanalizadores\Contracts\AutoanalizadorDriver;
use RuntimeException;

/**
 * Incam (bioquímica) — export CSV con separador `,`, sin fila de encabezado.
 *
 * Columnas 0-based (export aparato nuevo / INCAM1):
 * 2 nombre determinación, 8 protocolo / ID muestra, 11 texto resultado
 * (ej. `Resultado: 0.27mg/dl`).
 *
 * Omite filas de absorbancia (`Abs…`). Extrae el primer número del texto
 * (entero/decimal, signo opcional; coma o punto) descartando unidades y rótulos.
 * El nombre de la determinación en el CSV es el idAnalizador (misma cadena).
 * Varias filas del mismo protocolo se acumulan en un mapa idAnalizador => valor.
 */
final class IncamDriver implements AutoanalizadorDriver
{
    private const DELIMITADOR = ',';

    private const COL_DETERMINACION = 2;

    private const COL_PROTOCOLO = 8;

    private const COL_VALOR = 11;

    /**
     * Determinaciones reconocidas (nombre en archivo = idAnalizador, máx. 20).
     *
     * @var list<string>
     */
    private const DETERMINACIONES = [
        'BiliTotalLP-GT',
        'BiliDirectaLP-GT',
        'TrigliLiqP-GT',
        'GlucosaLiqP-GT',
        'ColesterolLiqP-GT',
        'ProteTLiquidP-GT',
        'AlbúminaLiqP-GT',
        'FosfatasaAlcalina-GT',
        'GOTLiqP-GT',
        'GPTLiqP-GT',
        'GammaGTLiqP-GT',
        'Creatinina-GT',
        'UreaUVLiqP-GT',
        'FosforoInorganico-GT',
        'Proteínas U/LCR',
        'CaArse-GT',
        'AmilasaLiqP-GT',
        'CPKNAC-GT',
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

                $idMuestra = trim((string) ($datos[self::COL_PROTOCOLO] ?? ''));
                if ($idMuestra === '' || $idMuestra !== $protocolo) {
                    continue;
                }

                $textoValor = (string) ($datos[self::COL_VALOR] ?? '');
                if (str_starts_with($textoValor, 'Abs')) {
                    continue;
                }

                $det = (string) ($datos[self::COL_DETERMINACION] ?? '');
                if ($det === '' || ! isset($permitidas[$det])) {
                    continue;
                }

                $numero = $this->extraerValorResultado($textoValor);
                if ($numero === null) {
                    continue;
                }

                $valores[$det] = $numero;
            }
        } finally {
            fclose($handle);
        }

        return $valores === [] ? null : $valores;
    }

    /**
     * Toma el primer número del texto (ej. `Resultado: -0.07mg/dl` → `-0.07`).
     */
    private function extraerValorResultado(string $textoValor): ?string
    {
        $textoValor = trim($textoValor);
        if ($textoValor === '') {
            return null;
        }

        if (preg_match('/-?\d+(?:[.,]\d+)?/', $textoValor, $m) !== 1) {
            return null;
        }

        $limpio = str_replace(',', '.', $m[0]);
        if ($limpio === '' || $limpio === '-' || $limpio === '.' || ! is_numeric($limpio)) {
            return null;
        }

        return $limpio;
    }
}
