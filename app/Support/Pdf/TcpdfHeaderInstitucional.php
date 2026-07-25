<?php

namespace App\Support\Pdf;

use App\Support\Entorno\LabInstitucional;
use TCPDF;
use Throwable;

/**
 * Encabezado institucional reutilizable en PDFs TCPDF.
 *
 * Si el laboratorio tiene `entorno.headerInforme` (membrete), se dibuja esa
 * imagen a ancho útil. Si no, logo + nombre + contacto.
 */
final class TcpdfHeaderInstitucional
{
    private const LOGO_ANCHO = 22.0;

    private const LOGO_ALTO = 22.0;

    private const MARGEN_LOGO_IZQ = 4.0;

    /** Alto máximo del membrete gráfico (mm), proporción al ancho útil. */
    private const MEMBRETE_MAX_ALTO = 35.0;

    /**
     * Dibuja membrete gráfico (si hay) o logo + datos centrados.
     *
     * @param  array{nombre?: string, direccion?: string, telefono?: string, logo_file?: ?string, header_file?: ?string}|null  $datos
     */
    public static function dibujar(
        TCPDF $pdf,
        float $margen,
        float $yInicio,
        float $anchoUtil,
        ?array $datos = null,
    ): float {
        $inst = $datos ?? LabInstitucional::datosParaPdf();

        $headerFile = is_string($inst['header_file'] ?? null) ? $inst['header_file'] : null;
        if ($headerFile !== null && is_file($headerFile)) {
            return self::dibujarMembrete($pdf, $margen, $yInicio, $anchoUtil, $headerFile);
        }

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

    private static function dibujarMembrete(
        TCPDF $pdf,
        float $margen,
        float $yInicio,
        float $anchoUtil,
        string $ruta,
    ): float {
        $alto = self::altoImagenEscalada($ruta, $anchoUtil, self::MEMBRETE_MAX_ALTO);

        try {
            $pdf->Image($ruta, $margen, $yInicio, $anchoUtil, $alto, '', '', '', false, 150, '', false, false, 0);
        } catch (Throwable) {
            TcpdfFuenteArial::aplicar($pdf, '', 8);
            $pdf->SetXY($margen, $yInicio);
            $pdf->Cell($anchoUtil, 5, '[Membrete no disponible]', 0, 1, 'C');

            return $yInicio + 8.0;
        }

        return $yInicio + $alto + 3.0;
    }

    private static function altoImagenEscalada(string $ruta, float $anchoMm, float $maxAltoMm): float
    {
        $info = @getimagesize($ruta);
        if (! is_array($info) || (int) ($info[0] ?? 0) <= 0) {
            return $maxAltoMm;
        }

        $proporcional = $anchoMm * ((float) $info[1] / (float) $info[0]);

        return min($maxAltoMm, max(8.0, $proporcional));
    }
}
