<?php

namespace App\Support\Pdf;

use App\Support\Entorno\LabInstitucional;
use TCPDF;

/**
 * Encabezado institucional reutilizable en PDFs TCPDF.
 */
final class TcpdfHeaderInstitucional
{
    private const LOGO_ANCHO = 22.0;

    private const LOGO_ALTO = 22.0;

    private const MARGEN_LOGO_IZQ = 4.0;

    /**
     * Dibuja logo a la izquierda y datos centrados en la página, alineados en altura.
     *
     * @param  array{nombre?: string, direccion?: string, telefono?: string, logo_file?: ?string}|null  $datos
     */
    public static function dibujar(
        TCPDF $pdf,
        float $margen,
        float $yInicio,
        float $anchoUtil,
        ?array $datos = null,
    ): float {
        $inst = $datos ?? LabInstitucional::datosParaPdf();
        $nombre = trim((string) ($inst['nombre'] ?? 'Laboratorio'));
        $direccion = trim((string) ($inst['direccion'] ?? ''));
        $telefono = trim((string) ($inst['telefono'] ?? ''));
        $logoFile = TcpdfLogoInstitucional::resolverArchivo(
            is_string($inst['logo_file'] ?? null) ? $inst['logo_file'] : null
        );

        TcpdfFuenteArial::aplicar($pdf, 'B', 11);
        $alturaNombre = 5.0;

        TcpdfFuenteArial::aplicar($pdf, '', 8);
        $alturaDireccion = $direccion !== '' ? 4.0 : 0.0;
        $textoTelefono = $telefono !== '' ? 'Tel: '.$telefono : '';
        $alturaTelefono = $textoTelefono !== '' ? 4.0 : 0.0;

        $alturaTexto = $alturaNombre + $alturaDireccion + $alturaTelefono;

        $tieneLogo = $logoFile !== null;
        $alturaBloque = $tieneLogo
            ? max(self::LOGO_ALTO, $alturaTexto)
            : $alturaTexto;

        if ($tieneLogo) {
            $yLogo = $yInicio + (($alturaBloque - self::LOGO_ALTO) / 2);
            TcpdfLogoInstitucional::dibujar(
                $pdf,
                $margen + self::MARGEN_LOGO_IZQ,
                $yLogo,
                self::LOGO_ANCHO,
                self::LOGO_ALTO,
                $logoFile,
            );
        }

        $yTexto = $yInicio + (($alturaBloque - $alturaTexto) / 2);

        TcpdfFuenteArial::aplicar($pdf, 'B', 11);
        $pdf->SetXY($margen, $yTexto);
        $pdf->Cell($anchoUtil, $alturaNombre, $nombre, 0, 1, 'C');

        TcpdfFuenteArial::aplicar($pdf, '', 8);
        if ($direccion !== '') {
            $pdf->SetX($margen);
            $pdf->Cell($anchoUtil, $alturaDireccion, $direccion, 0, 1, 'C');
        }
        if ($textoTelefono !== '') {
            $pdf->SetX($margen);
            $pdf->Cell($anchoUtil, $alturaTelefono, $textoTelefono, 0, 1, 'C');
        }

        $yLinea = $yInicio + $alturaBloque + 1.5;

        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line($margen, $yLinea, $margen + $anchoUtil, $yLinea);
        $pdf->SetDrawColor(0, 0, 0);

        return $yLinea + 3.0;
    }

    /**
     * Líneas centradas bajo el encabezado (título del documento, etc.).
     *
     * @param  list<array{0: string, 1?: string, 2?: int}>  $lineas  [texto, estilo, tamaño]
     */
    public static function dibujarLineasCentradas(TCPDF $pdf, float $y, array $lineas): float
    {
        foreach ($lineas as $linea) {
            $texto = (string) ($linea[0] ?? '');
            $estilo = (string) ($linea[1] ?? '');
            $tamano = (int) ($linea[2] ?? 9);

            if ($texto === '') {
                continue;
            }

            TcpdfFuenteArial::aplicar($pdf, $estilo, $tamano);
            $pdf->SetY($y);
            $pdf->Cell(0, 5, $texto, 0, 1, 'C');
            $y = $pdf->GetY();
        }

        return $y + 1.0;
    }
}
