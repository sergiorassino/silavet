<?php

namespace App\Support\CuentaCorriente;

use App\Support\Pdf\TcpdfFuenteArial;
use App\Support\Pdf\TcpdfHeaderInstitucional;
use Carbon\Carbon;
use TCPDF;

/**
 * Listado de clientes con saldo — TCPDF vertical A4.
 */
final class CuentaCorrienteClientesTcpdf extends TCPDF
{
    private const MARGEN = 12.0;

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
        $this->SetTitle('Cuenta corriente — Clientes');
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetAutoPageBreak(true, 12);
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
        $header = (array) ($this->datos['header'] ?? []);
        $anchoUtil = $this->getPageWidth() - (2 * self::MARGEN);

        $y = TcpdfHeaderInstitucional::dibujar(
            $this,
            self::MARGEN,
            self::MARGEN,
            $anchoUtil,
            $header,
        );

        $y = TcpdfHeaderInstitucional::dibujarLineasCentradas($this, $y, [
            ['Cuenta corriente — Clientes', 'B', 12],
            ['Saldo al '.Carbon::now()->format('d/m/Y'), '', 9],
        ]);
        $this->SetY($y);

        $w = $this->anchosColumnas();
        $titulos = ['Cliente', 'Dirección', 'Teléfono', 'Saldo'];
        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->SetFillColor(193, 215, 218);
        $this->SetTextColor(51, 51, 51);
        foreach ($titulos as $i => $titulo) {
            $this->Cell($w[$i], 6, $titulo, 1, 0, 'C', true);
        }
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0, 0, 0);
        $this->Ln();

        TcpdfFuenteArial::aplicar($this, '', 8);
        /** @var list<object> $filas */
        $filas = (array) ($this->datos['filas'] ?? []);
        $maxDireccion = max(20, (int) floor($w[1] / 2.2));
        foreach ($filas as $fila) {
            $this->Cell($w[0], 5, mb_substr((string) ($fila->nombre ?? ''), 0, 42), 1, 0, 'L');
            $this->Cell($w[1], 5, mb_substr((string) ($fila->direccion ?? ''), 0, $maxDireccion), 1, 0, 'L');
            $this->Cell($w[2], 5, (string) ($fila->telefono ?? ''), 1, 0, 'L');
            $this->Cell($w[3], 5, CuentaCorrienteConsulta::formatearMoneda((float) ($fila->saldo_total ?? 0)), 1, 1, 'R');
        }

        $this->Ln(3);
        TcpdfFuenteArial::aplicar($this, 'B', 9);
        $anchoEtiqueta = $w[0] + $w[1] + $w[2];
        $this->Cell($anchoEtiqueta, 5, 'Total saldo clientes', 0, 0, 'R');
        $this->Cell($w[3], 5, CuentaCorrienteConsulta::formatearMoneda((float) ($this->datos['saldo_total'] ?? 0)), 0, 1, 'R');
    }

    /**
     * @return list<float>
     */
    private function anchosColumnas(): array
    {
        $anchoUtil = $this->getPageWidth() - (self::MARGEN * 2);
        $wSaldo = 28.0;
        $wTelefono = 32.0;
        $wCliente = 52.0;
        $wDireccion = $anchoUtil - $wCliente - $wTelefono - $wSaldo;

        return [$wCliente, $wDireccion, $wTelefono, $wSaldo];
    }
}
