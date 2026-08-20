<?php

namespace App\Support\Pdf;

use App\Support\Entorno\EntornoArchivos;
use App\Support\Entorno\LabInstitucional;
use TCPDF;

/**
 * Logo institucional en PDFs TCPDF (ruta absoluta en disco).
 *
 * Escala dentro del recuadro máximo conservando la proporción (object-fit: contain).
 * Distingue logos cuadrados, horizontales y verticales para elegir el recuadro.
 */
final class TcpdfLogoInstitucional
{
    public const FORMA_CUADRADO = 'cuadrado';

    public const FORMA_HORIZONTAL = 'horizontal';

    public const FORMA_VERTICAL = 'vertical';

    /** Ratio w/h dentro de este rango se considera cuadrado. */
    private const RATIO_CUADRADO_MIN = 0.85;

    private const RATIO_CUADRADO_MAX = 1.15;

    public static function resolverArchivo(?string $logoFile = null): ?string
    {
        if (is_string($logoFile) && $logoFile !== '' && is_file($logoFile)) {
            return EntornoArchivos::prepararRutaParaTcpdf($logoFile);
        }

        $desdeEntorno = LabInstitucional::logoFile();
        if ($desdeEntorno !== null) {
            return $desdeEntorno;
        }

        return null;
    }

    /**
     * @return self::FORMA_*
     */
    public static function clasificarForma(string $ruta): string
    {
        $ratio = self::ratioPixeles($ruta);
        if ($ratio === null) {
            return self::FORMA_CUADRADO;
        }

        if ($ratio >= self::RATIO_CUADRADO_MIN && $ratio <= self::RATIO_CUADRADO_MAX) {
            return self::FORMA_CUADRADO;
        }

        return $ratio > 1.0 ? self::FORMA_HORIZONTAL : self::FORMA_VERTICAL;
    }

    /**
     * Dibuja el logo en ($x, $y) cabiendo en $anchoMax × $altoMax sin deformar.
     */
    public static function dibujar(
        TCPDF $pdf,
        float $x,
        float $y,
        float $anchoMax,
        float $altoMax,
        ?string $logoFile = null,
    ): void {
        $logo = self::resolverArchivo($logoFile);
        if ($logo === null) {
            return;
        }

        [$ancho, $alto] = self::dimensionesProporcionales($logo, $anchoMax, $altoMax);

        $pdf->Image($logo, $x, $y, $ancho, $alto, '', '', 'T', false, 300);
    }

    /**
     * Dibuja el logo en una banda del header: elige recuadro según forma
     * (cuadrado / rectangular) y lo centra verticalmente en la banda.
     *
     * @return array{0: float, 1: float}|null  Ancho y alto dibujados (mm), o null
     */
    public static function dibujarEnBanda(
        TCPDF $pdf,
        float $x,
        float $yBanda,
        float $anchoBanda,
        float $altoBanda,
        ?string $logoFile = null,
    ): ?array {
        $logo = self::resolverArchivo($logoFile);
        if ($logo === null || $anchoBanda <= 0 || $altoBanda <= 0) {
            return null;
        }

        [$anchoMax, $altoMax] = self::recuadroSegunForma($logo, $anchoBanda, $altoBanda);
        [$ancho, $alto] = self::dimensionesProporcionales($logo, $anchoMax, $altoMax);

        $y = $yBanda + (($altoBanda - $alto) / 2);

        $pdf->Image($logo, $x, $y, $ancho, $alto, '', '', 'T', false, 300);

        return [$ancho, $alto];
    }

    /**
     * Recuadro máximo según forma del logo, dentro de la banda disponible.
     *
     * - Cuadrado: lado = alto de banda (ocupa la altura del header).
     * - Horizontal: todo el ancho × alto de la banda (p. ej. 1/3 izq. del membrete).
     * - Vertical: ancho acotado al alto (no invade el contacto) × alto de banda.
     *
     * @return array{0: float, 1: float}
     */
    public static function recuadroSegunForma(
        string $ruta,
        float $anchoBanda,
        float $altoBanda,
    ): array {
        return match (self::clasificarForma($ruta)) {
            self::FORMA_CUADRADO => [
                min($anchoBanda, $altoBanda),
                min($anchoBanda, $altoBanda),
            ],
            self::FORMA_VERTICAL => [
                min($anchoBanda, $altoBanda),
                $altoBanda,
            ],
            default => [
                $anchoBanda,
                $altoBanda,
            ],
        };
    }

    /**
     * Ancho y alto en mm que caben en el recuadro máximo sin alterar el aspecto.
     *
     * @return array{0: float, 1: float}
     */
    public static function dimensionesProporcionales(
        string $ruta,
        float $anchoMax,
        float $altoMax,
    ): array {
        $ratio = self::ratioPixeles($ruta);
        if ($ratio === null || $anchoMax <= 0 || $altoMax <= 0) {
            return [$anchoMax, $altoMax];
        }

        $ancho = $anchoMax;
        $alto = $ancho / $ratio;

        if ($alto > $altoMax) {
            $alto = $altoMax;
            $ancho = $alto * $ratio;
        }

        return [$ancho, $alto];
    }

    private static function ratioPixeles(string $ruta): ?float
    {
        $info = @getimagesize($ruta);
        $pxW = is_array($info) ? (int) ($info[0] ?? 0) : 0;
        $pxH = is_array($info) ? (int) ($info[1] ?? 0) : 0;

        if ($pxW <= 0 || $pxH <= 0) {
            return null;
        }

        return $pxW / $pxH;
    }
}
