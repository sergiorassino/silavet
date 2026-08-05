<?php

namespace App\Support\CuentaCorriente;

use App\Support\Pdf\TcpdfFuenteArial;
use App\Support\Pdf\TcpdfHeaderInstitucional;
use Carbon\Carbon;
use TCPDF;

/**
 * Detalle de cuenta corriente por cliente, variante tesoreria_pacientes
 * (tabla `movimientos`) — TCPDF vertical A4.
 *
 * Columnas: #, Nombre, Cuenta, Concepto, Fecha/Hora, Monto, Obs.
 */
final class CuentaCorrienteMovimientosDetalleTcpdf extends TCPDF
{
    private const MARGEN = 8.0;

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
        $this->SetTitle('Cuenta corriente — Detalle');
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetAutoPageBreak(true, 10);
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

        $lineas = [
            ['Cuenta corriente — Detalle', 'B', 11],
        ];
        $clienteNombre = trim((string) ($this->datos['cliente_nombre'] ?? ''));
        if ($clienteNombre !== '') {
            $lineas[] = [$clienteNombre, 'B', 9];
        }
        $lineas[] = ['Período: '.(string) ($this->datos['periodo_texto'] ?? 'Todo el historial'), '', 8];

        $y = TcpdfHeaderInstitucional::dibujarLineasCentradas($this, $y, $lineas);
        $this->SetY($y);

        $w = $this->anchosColumnas();
        $titulos = ['#', 'Nombre', 'Cuenta', 'Concepto', 'Fecha/Hora', 'Monto', 'Obs'];
        TcpdfFuenteArial::aplicar($this, 'B', 6);
        $this->SetFillColor(193, 215, 218);
        $this->SetTextColor(51, 51, 51);
        foreach ($titulos as $i => $titulo) {
            $this->Cell($w[$i], 5, $titulo, 1, 0, 'C', true);
        }
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0, 0, 0);
        $this->Ln();

        TcpdfFuenteArial::aplicar($this, '', 6);
        /** @var list<object> $filas */
        $filas = (array) ($this->datos['filas'] ?? []);
        $numero = 1;
        $maxChars = $this->maximosCaracteresColumna($w);
        foreach ($filas as $fila) {
            $fechhora = $fila->fechhora !== ''
                ? Carbon::parse($fila->fechhora)->format('d/m/Y H:i:s')
                : '';
            $monto = round((float) ($fila->monto ?? 0), 2);
            $esNegativo = $monto < 0;

            if ($esNegativo) {
                $this->SetFillColor(255, 252, 220);
                $fill = true;
            } else {
                $fill = false;
            }

            $this->Cell($w[0], 4, (string) $numero, 1, 0, 'C', $fill);
            $this->Cell($w[1], 4, $this->truncar((string) ($fila->etiquetaPaciente ?? ''), $maxChars[1]), 1, 0, 'L', $fill);
            $this->Cell($w[2], 4, $this->truncar((string) ($fila->cuentaLabel ?? ''), $maxChars[2]), 1, 0, 'C', $fill);
            $this->Cell($w[3], 4, $this->truncar((string) ($fila->concepto ?? ''), $maxChars[3]), 1, 0, 'L', $fill);
            $this->Cell($w[4], 4, $fechhora, 1, 0, 'C', $fill);
            $this->Cell($w[5], 4, CuentaCorrienteMovimientosConsulta::formatearMoneda($monto), 1, 0, 'R', $fill);
            $this->Cell($w[6], 4, $this->truncar((string) ($fila->obs ?? ''), $maxChars[6]), 1, 1, 'L', $fill);

            if ($fill) {
                $this->SetFillColor(255, 255, 255);
            }

            $numero++;
        }

        $saldoAnterior = $this->datos['saldo_anterior'] ?? null;
        $fechaDesde = trim((string) ($this->datos['fecha_desde'] ?? ''));
        if ($saldoAnterior !== null && $fechaDesde !== '') {
            TcpdfFuenteArial::aplicar($this, 'B', 6);
            $anchoEtiqueta = array_sum(array_slice($w, 0, 5));
            $this->Cell($anchoEtiqueta, 4, 'Saldo anterior al '.Carbon::parse($fechaDesde)->format('d/m/Y'), 1, 0, 'R', true);
            $this->Cell($w[5], 4, CuentaCorrienteMovimientosConsulta::formatearMoneda((float) $saldoAnterior), 1, 0, 'R', true);
            $this->Cell($w[6], 4, '', 1, 1, 'L', true);
            TcpdfFuenteArial::aplicar($this, '', 6);
        }

        $this->Ln(2);
        TcpdfFuenteArial::aplicar($this, 'B', 7);
        $anchoEtiqueta = array_sum(array_slice($w, 0, 5));
        $this->Cell($anchoEtiqueta, 4, 'Total período:', 0, 0, 'R');
        $this->Cell($w[5], 4, CuentaCorrienteMovimientosConsulta::formatearMoneda((float) ($this->datos['total_monto'] ?? 0)), 0, 1, 'R');
    }

    /**
     * @return list<float>
     */
    private function anchosColumnas(): array
    {
        $anchoUtil = $this->getPageWidth() - (self::MARGEN * 2);
        $wNum = 5.0;
        $wCuenta = 16.0;
        $wFechhora = 30.0;
        $wMonto = 22.0;
        $wConcepto = 28.0;
        $wNombre = 28.0;
        $wObs = max(18.0, $anchoUtil - ($wNum + $wNombre + $wCuenta + $wConcepto + $wFechhora + $wMonto));

        return [$wNum, $wNombre, $wCuenta, $wConcepto, $wFechhora, $wMonto, $wObs];
    }

    /**
     * @param  list<float>  $anchos
     * @return list<int>
     */
    private function maximosCaracteresColumna(array $anchos): array
    {
        return array_map(fn (float $ancho) => max(4, (int) floor($ancho / 1.7)), $anchos);
    }

    private function truncar(string $texto, int $maximo): string
    {
        return mb_substr($texto, 0, $maximo);
    }
}
