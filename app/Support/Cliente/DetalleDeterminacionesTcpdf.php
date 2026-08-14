<?php

namespace App\Support\Cliente;

use App\Support\Pdf\TcpdfFuenteArial;
use App\Support\Pdf\TcpdfHeaderInstitucional;
use TCPDF;

/**
 * Detalle de determinaciones pedidas (autogestión del veterinario) — A4 vertical.
 */
final class DetalleDeterminacionesTcpdf extends TCPDF
{
    private const MARGEN = 12.0;

    private const ALTO_FILA = 6.0;

    private const ANCHO_NOMBRE = 120.0;

    private const ANCHO_IMPORTE = 33.0;

    /** @var array<string, mixed> */
    private array $datos;

    /**
     * @param  array<string, mixed>  $datos
     */
    private function __construct(array $datos)
    {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->datos = $datos;
        $this->SetCreator(config('app.name', 'SILAVET'));
        $this->SetAuthor(config('app.name', 'SILAVET'));
        $this->SetTitle('Determinaciones solicitadas');
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetAutoPageBreak(true, 14);
        $this->SetMargins(self::MARGEN, self::MARGEN, self::MARGEN);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function generar(array $datos): self
    {
        $pdf = new self($datos);
        $pdf->AddPage();
        $pdf->dibujar();

        return $pdf;
    }

    public static function nombreArchivo(array $datos): string
    {
        $base = trim((string) ($datos['protocolo'] ?? '').'_'.(string) ($datos['paciente'] ?? ''));
        $base = preg_replace('/[^\p{L}\p{N}_\- ]+/u', '', $base) ?: 'determinaciones';
        $base = preg_replace('/\s+/', '_', trim($base)) ?: 'determinaciones';
        $base = mb_substr($base, 0, 60);

        return 'determinaciones_'.$base.'.pdf';
    }

    public static function respuestaHttp(self $pdf, string $nombreArchivo): \Illuminate\Http\Response
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $binario = $pdf->Output($nombreArchivo, 'S');
        $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', $nombreArchivo) ?: 'determinaciones.pdf';
        $disposition = 'inline; filename="'.$ascii.'"; filename*=UTF-8\'\''.rawurlencode($nombreArchivo);

        return response($binario, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function dibujar(): void
    {
        $header = (array) ($this->datos['header'] ?? []);
        $anchoUtil = $this->getPageWidth() - (2 * self::MARGEN);

        $y = TcpdfHeaderInstitucional::dibujar(
            $this,
            self::MARGEN,
            self::MARGEN,
            $anchoUtil,
            $header !== [] ? $header : null,
        );

        $y = TcpdfHeaderInstitucional::dibujarLineasCentradas($this, $y, [
            ['Determinaciones solicitadas', 'B', 11],
            [(string) ($this->datos['cliente'] ?? ''), 'B', 9],
            [(string) ($this->datos['paciente'] ?? ''), '', 9],
            ['Protocolo '.(string) ($this->datos['protocolo'] ?? ''), '', 8],
        ]);
        $this->SetY($y);

        $this->dibujarTabla();
    }

    private function dibujarTabla(): void
    {
        $w = [self::ANCHO_NOMBRE, self::ANCHO_IMPORTE, self::ANCHO_IMPORTE];

        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->SetFillColor(193, 215, 218);
        $this->SetTextColor(51, 51, 51);
        $this->Cell($w[0], 6.0, 'Determinaciones Solicitadas', 1, 0, 'L', true);
        $this->Cell($w[1], 6.0, 'Precio', 1, 0, 'R', true);
        $this->Cell($w[2], 6.0, 'Descuento', 1, 1, 'R', true);

        $this->SetTextColor(0, 0, 0);
        TcpdfFuenteArial::aplicar($this, '', 8);

        /** @var list<array{nombre: string, neto_fmt: string, descuento_fmt: string}> $filas */
        $filas = (array) ($this->datos['filas'] ?? []);
        if ($filas === []) {
            $this->Cell(array_sum($w), 8.0, 'No hay determinaciones solicitadas.', 1, 1, 'C');
        } else {
            foreach ($filas as $fila) {
                $this->Cell($w[0], self::ALTO_FILA, $this->truncar((string) ($fila['nombre'] ?? ''), 62), 1, 0, 'L');
                $this->Cell($w[1], self::ALTO_FILA, (string) ($fila['neto_fmt'] ?? ''), 1, 0, 'R');
                $this->Cell($w[2], self::ALTO_FILA, (string) ($fila['descuento_fmt'] ?? ''), 1, 1, 'R');
            }
        }

        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->SetFillColor(235, 235, 235);
        $this->Cell($w[0], 6.5, 'Total Acumulado -', 1, 0, 'L', true);
        $this->Cell($w[1], 6.5, (string) ($this->datos['total_neto_fmt'] ?? '0,00'), 1, 0, 'R', true);
        $this->Cell($w[2], 6.5, (string) ($this->datos['total_descuento_fmt'] ?? '0,00'), 1, 1, 'R', true);

        $this->Ln(4);
        TcpdfFuenteArial::aplicar($this, 'B', 10);
        $this->Cell(0, 6, 'Total con Descuentos: '.(string) ($this->datos['total_con_descuento_fmt'] ?? '0,00'), 0, 1, 'L');
    }

    private function truncar(string $texto, int $max): string
    {
        $texto = trim($texto);
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }

        return mb_substr($texto, 0, $max - 1).'…';
    }
}
