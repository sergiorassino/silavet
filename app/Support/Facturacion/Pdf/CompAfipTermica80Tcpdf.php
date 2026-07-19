<?php

namespace App\Support\Facturacion\Pdf;

use App\Support\Afip\AfipCondicionIvaReceptor;
use App\Support\Pdf\TcpdfFuenteArial;
use TCPDF;

/**
 * Comprobante AFIP térmico 80 mm (legado Scriptcase).
 */
final class CompAfipTermica80Tcpdf extends TCPDF
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
        $this->SetTitle((string) ($datos['titulo'] ?? 'Ticket AFIP'));
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
        $cbteTipo = (int) ($d['CbteTipo'] ?? 0);
        $importe = (float) ($d['importe'] ?? 0);
        $importeFmt = number_format($importe, 2, ',', '.');

        $logo = $d['logo_file'] ?? null;
        if (is_string($logo) && $logo !== '' && is_file($logo)) {
            $logoW = 68.0;
            $logoH = 30.0;
            $this->Image($logo, (80 - $logoW) / 2, -3, $logoW, $logoH, '', '', '', false, 300, '', false, false, 0);
            $this->SetY($logoH - 7);
        }

        $tipo = match ($cbteTipo) {
            12 => 'NOTA DE CRÉDITO C',
            15 => 'RECIBO C',
            default => 'FACTURA C',
        };

        $y = $this->GetY();
        $this->Rect($x, $y + 1, $w, 8);
        $this->SetXY($x, $y + 1);
        TcpdfFuenteArial::aplicar($this, 'B', 10);
        $this->Cell($w, 5, $tipo, 0, 1, 'C');
        $this->SetXY($x, $y + 5);
        TcpdfFuenteArial::aplicar($this, '', 6);
        $this->Cell($w, 4, 'Cod. '.$cbteTipo, 0, 1, 'C');

        $this->SetXY($x, $this->GetY() + 1);
        TcpdfFuenteArial::aplicar($this, 'B', 9);
        $this->Cell(0, 4, (string) ($d['razonSocial'] ?? ''), 0, 1, 'C');
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(0, 4, (string) ($d['domicComerc'] ?? ''), 0, 1, 'C');
        $this->Ln(2);

        TcpdfFuenteArial::aplicar($this, '', 7);
        $this->Cell(36, 4, 'CUIT: '.(string) ($d['cuit'] ?? ''), 0, 0, 'L');
        $this->Cell(36, 4, 'IVA: '.(string) ($d['condIvaEmisor'] ?? ''), 0, 1, 'R');
        $this->Cell(36, 4, 'IIBB: '.(string) ($d['ingresosBrutos'] ?? ''), 0, 0, 'L');
        $this->Cell(36, 4, 'Inicio: '.(string) ($d['inicioActiv'] ?? '—'), 0, 1, 'R');
        $this->Line(4, $this->GetY() + 1, 76, $this->GetY() + 1);

        $this->SetXY($x, $this->GetY() + 3);
        $this->Cell(36, 4, 'Nro: '.(string) ($d['numero_formateado'] ?? ''), 0, 0, 'L');
        $this->Cell(36, 4, 'Fecha: '.(string) ($d['fechaComprobante'] ?? ''), 0, 1, 'R');

        $this->Ln(1);
        $this->Cell(50, 4, 'Cliente: '.(string) ($d['razonSocialCliente'] ?? ''), 0, 0, 'L');
        $this->Cell(22, 4, 'Doc: '.(string) ($d['DocNro'] ?? ''), 0, 1, 'R');
        $condId = (int) ($d['CondicionIVAReceptorId'] ?? 0);
        $this->Cell(0, 4, 'IVA: '.AfipCondicionIvaReceptor::etiquetaDesdeId($condId), 0, 1, 'L');
        $this->Line(4, $this->GetY(), 76, $this->GetY());

        TcpdfFuenteArial::aplicar($this, 'B', 7);
        $this->Cell(40, 5, 'Descripción', 0, 0);
        $this->Cell(16, 5, 'Precio', 0, 0, 'R');
        $this->Cell(16, 5, 'Total', 0, 1, 'R');
        TcpdfFuenteArial::aplicar($this, '', 7);

        $concepto = trim((string) ($d['conceptoFacturado'] ?? ''));
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

        $urlQr = trim((string) ($d['url_qr'] ?? ''));
        if ($urlQr !== '') {
            $this->write2DBarcode($urlQr, 'QRCODE,L', 4, $this->GetY(), 25, 25);
            $this->SetY($this->GetY() + 26);
        }

        TcpdfFuenteArial::aplicar($this, '', 7);
        $this->Cell(0, 4, 'CAE: '.(string) ($d['CAE'] ?? ''), 0, 1, 'R');
        $this->Cell(0, 4, 'Vto CAE: '.(string) ($d['CAEFchVto'] ?? ''), 0, 1, 'R');
    }
}
