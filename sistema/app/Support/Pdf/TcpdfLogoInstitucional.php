<?php

namespace App\Support\Pdf;

use TCPDF;

/**
 * Logo institucional en PDFs TCPDF (ruta absoluta en disco).
 */
final class TcpdfLogoInstitucional
{
    public static function resolverArchivo(?string $logoFile = null): ?string
    {
        if (is_string($logoFile) && $logoFile !== '' && is_file($logoFile)) {
            return $logoFile;
        }

        $desdeEntorno = \App\Support\Entorno\LabInstitucional::logoFile();
        if ($desdeEntorno !== null) {
            return $desdeEntorno;
        }

        return null;
    }

    public static function dibujar(
        TCPDF $pdf,
        float $x,
        float $y,
        float $ancho,
        float $alto,
        ?string $logoFile = null,
    ): void {
        $logo = self::resolverArchivo($logoFile);
        if ($logo === null) {
            return;
        }

        $pdf->Image($logo, $x, $y, $ancho, $alto, '', '', 'T', false, 300);
    }
}
