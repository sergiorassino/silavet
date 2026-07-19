<?php

namespace App\Support\Facturacion\Pdf;

use App\Support\Pdf\TcpdfFuenteArial;
use TCPDF;

/**
 * Comanda interna A4 vertical (CbteTipo 888) — sin AFIP / QR / CAE.
 */
final class CompAfipComandaA4Tcpdf extends TCPDF
{
    private const MARGEN = 30.0;

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
        $this->SetTitle('Comanda');
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetAutoPageBreak(false, 10);
        $this->SetMargins(self::MARGEN, 20, self::MARGEN);
        $this->SetDisplayMode('real');
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

    public static function respuestaHttp(self $pdf, string $nombreArchivo): \Illuminate\Http\Response
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $binario = $pdf->Output($nombreArchivo, 'S');

        return response($binario, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$nombreArchivo.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function dibujar(): void
    {
        $d = $this->datos;
        $importe = (float) ($d['importe'] ?? 0);
        $importeFmt = number_format($importe, 2, ',', '.');

        $this->SetDrawColor(0, 0, 0);
        $this->SetFillColor(220, 220, 220);
        $this->Line(30, 30, 180, 30);

        // Letra X (no válido como factura).
        $this->SetXY(95, 30);
        TcpdfFuenteArial::aplicar($this, 'B', 20);
        $this->Cell(20, 15, 'X', 1, 1, 'C');
        TcpdfFuenteArial::aplicar($this, '', 7);
        $this->SetX(95);
        $this->Cell(20, 3, 'Comanda', 0, 0, 'C');

        $this->SetXY(30, 48);
        TcpdfFuenteArial::aplicar($this, 'B', 12);
        $this->Cell(80, 5, (string) ($d['razonSocial'] ?? ''), 0, 0, 'L');

        $this->SetXY(120, 48);
        TcpdfFuenteArial::aplicar($this, 'B', 10);
        $this->Cell(30, 5, 'COMANDA:  ', 0, 0, 'L');
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(30, 5, (string) ($d['CbteHasta'] ?? ''), 0, 0, 'L');

        $this->SetXY(30, 54);
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(80, 5, (string) ($d['domicComerc'] ?? ''), 0, 0, 'L');

        $this->SetXY(120, 54);
        $this->SetFillColor(220, 220, 220);
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(60, 5, 'Fecha  '.(string) ($d['fechaComprobante'] ?? ''), 1, 0, 'C', true);

        $this->SetXY(30, 62);
        TcpdfFuenteArial::aplicar($this, 'B', 9);
        $this->Cell(150, 5, 'Documento no válido como factura', 0, 1, 'L');

        $y = 72;
        $this->Line(30, $y, 180, $y);
        $this->SetXY(30, $y + 2);
        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->Cell(25, 5, 'DNI / CUIT: ', 0, 0, 'L');
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(100, 5, (string) ($d['DocNro'] ?? ''), 0, 1, 'L');

        $this->SetX(30);
        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->Cell(55, 5, 'Apellido y Nombre / Razón Social: ', 0, 0, 'L');
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(90, 5, (string) ($d['razonSocialCliente'] ?? ''), 0, 1, 'L');

        $this->SetX(30);
        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->Cell(40, 5, 'Condición frente a IVA:', 0, 0, 'L');
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(70, 5, 'SIN DATOS', 0, 1, 'L');

        $this->Ln(2);
        $this->SetX(30);
        $this->SetFillColor(0, 0, 0);
        $this->SetTextColor(255, 255, 255);
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(110, 5, 'Descripción', 1, 0, 'L', true);
        $this->Cell(20, 5, 'Precio', 1, 0, 'R', true);
        $this->Cell(20, 5, 'Total', 1, 1, 'R', true);

        $this->SetTextColor(0, 0, 0);
        $this->SetX(30);
        $this->Cell(110, 5, (string) ($d['conceptoFacturado'] ?? 'Servicios de laboratorio'), 0, 0, 'L');
        $this->Cell(20, 5, $importeFmt, 0, 0, 'R');
        $this->Cell(20, 5, $importeFmt, 0, 1, 'R');

        $this->SetXY(30, 200);
        $this->SetFillColor(0, 0, 0);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(150, 5, 'TOTAL', 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $this->SetX(30);
        $this->Cell(150, 5, '$ '.$importeFmt, 1, 1, 'C');

        $this->Ln(8);
        $this->SetX(30);
        TcpdfFuenteArial::aplicar($this, 'I', 8);
        $this->Cell(150, 5, 'Comprobante interno — no válido como factura ni documento fiscal.', 0, 1, 'C');
    }
}
