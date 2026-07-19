<?php

namespace App\Support\Facturacion\Pdf;

use App\Support\Afip\AfipCondicionIvaReceptor;
use App\Support\Pdf\TcpdfFuenteArial;
use TCPDF;

/**
 * Comprobante AFIP A4 vertical (adaptado del legado Scriptcase/FPDF).
 */
final class CompAfipA4Tcpdf extends TCPDF
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
        $this->SetTitle((string) ($datos['titulo'] ?? 'Comprobante AFIP'));
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
        $cbteTipo = (int) ($d['CbteTipo'] ?? 0);
        $importe = (float) ($d['importe'] ?? 0);

        $this->SetDrawColor(0, 0, 0);
        $this->SetFillColor(220, 220, 220);
        $this->Line(30, 30, 180, 30);

        $this->SetXY(95, 30);
        TcpdfFuenteArial::aplicar($this, '', 20);
        $this->Cell(20, 15, (string) ($d['letra'] ?? 'C'), 1, 1, 'C');
        TcpdfFuenteArial::aplicar($this, '', 7);
        $this->SetX(95);
        $this->Cell(20, 3, 'Cod. '.$cbteTipo, 0, 0, 'C');

        $this->SetXY(30, 48);
        TcpdfFuenteArial::aplicar($this, 'B', 12);
        $this->Cell(80, 5, (string) ($d['razonSocial'] ?? ''), 0, 0, 'L');

        $this->SetXY(120, 48);
        $tituloDoc = match ($cbteTipo) {
            12 => 'NOTA DE CRÉDITO:  ',
            15 => 'RECIBO:  ',
            default => 'FACTURA:  ',
        };
        TcpdfFuenteArial::aplicar($this, 'B', 10);
        $this->Cell(38, 5, $tituloDoc, 0, 0, 'L');
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(30, 5, (string) ($d['numero_formateado'] ?? ''), 0, 0, 'L');

        $this->SetXY(120, 54);
        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->Cell(20, 5, 'CUIT:  ', 0, 0, 'L');
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(40, 5, (string) ($d['cuit'] ?? ''), 0, 0, 'L');

        $this->SetXY(30, 59);
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(80, 5, (string) ($d['domicComerc'] ?? ''), 0, 0, 'L');

        $this->SetXY(120, 59);
        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->Cell(35, 5, 'Ingresos Brutos:  ', 0, 0, 'L');
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(30, 5, (string) ($d['ingresosBrutos'] ?? ''), 0, 0, 'L');

        $this->SetXY(30, 65);
        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->Cell(40, 5, 'Inicio de Actividades:  ', 0, 0, 'L');
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(40, 5, (string) ($d['inicioActiv'] ?? '—'), 0, 0, 'L');

        $this->SetXY(30, 71);
        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->Cell(80, 5, (string) ($d['condIvaEmisor'] ?? 'Responsable Monotributo'), 0, 0, 'L');

        $this->SetXY(120, 71);
        $this->SetFillColor(220, 220, 220);
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(60, 5, 'Fecha  '.(string) ($d['fechaComprobante'] ?? ''), 1, 0, 'C', true);

        $y = 82;
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
        $condId = (int) ($d['CondicionIVAReceptorId'] ?? 0);
        $this->Cell(70, 5, AfipCondicionIvaReceptor::etiquetaDesdeId($condId), 0, 1, 'L');

        $this->SetX(30);
        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->Cell(35, 4, 'Condición de venta:', 0, 0, 'L');
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(60, 4, 'Cuenta Corriente', 0, 1, 'L');

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
        $importeFmt = number_format($importe, 2, ',', '.');
        $this->Cell(110, 5, (string) ($d['conceptoFacturado'] ?? ''), 0, 0, 'L');
        $this->Cell(20, 5, $importeFmt, 0, 0, 'R');
        $this->Cell(20, 5, $importeFmt, 0, 1, 'R');

        $this->SetXY(30, 200);
        $this->SetFillColor(0, 0, 0);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(150, 5, 'TOTAL', 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $this->SetX(30);
        $this->Cell(150, 5, '$ '.$importeFmt, 1, 1, 'C');

        $yCae = $this->GetY() + 8;
        $this->SetXY(120, $yCae);
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->Cell(60, 5, 'CAE: '.(string) ($d['CAE'] ?? ''), 0, 1, 'L');
        $this->SetX(120);
        $this->Cell(60, 5, 'VTO. CAE:  '.(string) ($d['CAEFchVto'] ?? ''), 0, 1, 'L');

        $urlQr = trim((string) ($d['url_qr'] ?? ''));
        if ($urlQr !== '') {
            $this->write2DBarcode($urlQr, 'QRCODE,L', 30, $yCae, 40, 40);
        }
    }
}
