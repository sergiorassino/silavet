<?php

namespace App\Support\Facturacion\Pdf;

use App\Support\Pdf\TcpdfFuenteArial;
use TCPDF;

/**
 * Comanda interna 80 mm (CbteTipo 888) — sin AFIP / QR / CAE.
 */
final class CompAfipComandaTcpdf extends TCPDF
{
    /** @var array<string, mixed> */
    private array $datos;

    /**
     * @param  array<string, mixed>  $datos
     */
    private function __construct(array $datos)
    {
        parent::__construct('P', 'mm', [80, 297], true, 'UTF-8', false);
        $this->datos = $datos;
        $this->SetCreator(config('app.name', 'SILAVET'));
        $this->SetAuthor(config('app.name', 'SILAVET'));
        $this->SetTitle('Comanda');
        $this->SetMargins(4, 0, 4);
        $this->SetAutoPageBreak(true, 4);
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
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
        $x = 4.0;
        $w = 72.0;
        $importe = (float) ($d['importe'] ?? 0);
        $importeFmt = number_format($importe, 2, ',', '.');

        $logo = $d['logo_file'] ?? null;
        if (is_string($logo) && $logo !== '' && is_file($logo)) {
            $logoW = 68.0;
            $logoH = 30.0;
            $this->Image($logo, (80 - $logoW) / 2, -3, $logoW, $logoH, '', '', '', false, 300, '', false, false, 0);
            $this->SetY($logoH - 7);
        }

        $y = $this->GetY();
        $marcoY = $y + 1.0;
        $marcoH = 8.0;
        $this->Rect($x, $marcoY, $w, $marcoH);
        $this->SetXY($x, $marcoY);
        TcpdfFuenteArial::aplicar($this, 'B', 12);
        $this->Cell($w, $marcoH, 'X', 0, 1, 'C');

        $this->SetXY($x, $marcoY + $marcoH + 1.5);
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(0, 4, 'Documento no válido como factura', 0, 1, 'C');
        $this->Ln(2);
        $this->Line(4, $this->GetY() + 1, 76, $this->GetY() + 1);

        $this->SetXY($x, $this->GetY() + 4);
        TcpdfFuenteArial::aplicar($this, '', 7);
        $this->Cell(36, 4, 'Nro: '.(string) ($d['CbteHasta'] ?? ''), 0, 0, 'L');
        $this->Cell(36, 4, 'Fecha: '.(string) ($d['fechaComprobante'] ?? ''), 0, 1, 'R');

        $this->Ln(1);
        $this->Cell(50, 4, 'Cliente: '.(string) ($d['razonSocialCliente'] ?? ''), 0, 0, 'L');
        $this->Cell(22, 4, 'Doc: '.(string) ($d['DocNro'] ?? ''), 0, 1, 'R');
        $this->Cell(0, 4, 'IVA: SIN DATOS', 0, 1, 'L');
        $this->Line(4, $this->GetY(), 76, $this->GetY());

        TcpdfFuenteArial::aplicar($this, 'B', 7);
        $this->Cell(40, 5, 'Descripción', 0, 0);
        $this->Cell(16, 5, 'Precio', 0, 0, 'R');
        $this->Cell(16, 5, 'Total', 0, 1, 'R');
        TcpdfFuenteArial::aplicar($this, '', 7);

        $concepto = trim((string) ($d['conceptoFacturado'] ?? 'Servicios de laboratorio'));
        $yIni = $this->GetY();
        $this->MultiCell(40, 4, $concepto, 0, 'L', false, 0, $x, $yIni);
        $yDesc = $this->GetY();
        $this->SetXY($x + 40, $yIni);
        $this->Cell(16, 4, $importeFmt, 0, 0, 'R');
        $this->Cell(16, 4, $importeFmt, 0, 0, 'R');
        $this->SetY(max($yDesc, $yIni + 4) + 1);
        $this->Line(4, $this->GetY(), 76, $this->GetY());

        $this->Ln(1);
        TcpdfFuenteArial::aplicar($this, 'B', 9);
        $this->Cell(0, 6, 'TOTAL $ '.$importeFmt, 0, 1, 'R');
    }
}
