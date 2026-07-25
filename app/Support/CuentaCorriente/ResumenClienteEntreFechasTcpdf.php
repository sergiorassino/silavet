<?php

namespace App\Support\CuentaCorriente;

use App\Support\Pdf\TcpdfFuenteArial;
use App\Support\Pdf\TcpdfHeaderInstitucional;
use Carbon\Carbon;
use TCPDF;

/**
 * Resumen de movimientos de un cliente entre fechas — TCPDF.
 *
 * Orientación horizontal (A4 landscape) explícita: replica el listado
 * NeoLab «Resumen cliente entre fechas» (muchas columnas en una fila).
 */
final class ResumenClienteEntreFechasTcpdf extends TCPDF
{
    private const MARGEN = 10.0;

    private const ALTO_ENCABEZADO = 6.0;

    private const ALTO_FILA = 5.0;

    /** @var array<string, mixed> */
    private array $datos;

    /**
     * @param  array<string, mixed>  $datos
     */
    private function __construct(array $datos)
    {
        parent::__construct('L', 'mm', 'A4', true, 'UTF-8', false);
        $this->datos = $datos;
        $this->SetCreator(config('app.name', 'SILAVET'));
        $this->SetAuthor(config('app.name', 'SILAVET'));
        $this->SetTitle('Resumen cliente entre fechas');
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

        $clienteNombre = trim((string) ($this->datos['cliente_nombre'] ?? ''));
        $fechaDesde = trim((string) ($this->datos['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($this->datos['fecha_hasta'] ?? ''));
        $saldoActual = (float) ($this->datos['saldo_actual'] ?? 0);

        $desdeTxt = $fechaDesde !== '' ? Carbon::parse($fechaDesde)->format('d/m/Y') : '—';
        $hastaTxt = $fechaHasta !== '' ? Carbon::parse($fechaHasta)->format('d/m/Y') : '—';
        $titulo = 'Resumen: '.$clienteNombre
            .' entre el '.$desdeTxt.' y el '.$hastaTxt
            .' -- SALDO ACTUAL: $ '.CuentaCorrienteConsulta::formatearMoneda($saldoActual);

        TcpdfFuenteArial::aplicar($this, '', 10);
        $this->SetY($y);
        $this->Cell($anchoUtil, 8, $titulo, 0, 1, 'L');
        $this->Ln(2);

        $w = $this->anchosColumnas();
        $titulos = [
            'Nombre', 'Protocolo', 'Especie', 'Raza', 'Fecha', 'Propietario',
            'Estado', 'Precio', 'Descuento', 'Pagado', 'Saldo',
        ];

        TcpdfFuenteArial::aplicar($this, 'I', 6);
        $this->SetFillColor(193, 215, 218);
        $this->SetTextColor(51, 51, 51);
        foreach ($titulos as $i => $tituloCol) {
            $this->Cell($w[$i], self::ALTO_ENCABEZADO, $tituloCol, 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0, 0, 0);

        TcpdfFuenteArial::aplicar($this, '', 7);
        $maxChars = $this->maximosCaracteresColumna($w);

        /** @var list<object> $filas */
        $filas = (array) ($this->datos['filas'] ?? []);
        foreach ($filas as $fila) {
            $fecha = $fila->fechhoy !== '' ? Carbon::parse($fila->fechhoy)->format('d/m/Y') : '';
            $esPago = (bool) ($fila->esPagoGlobal ?? false);

            $this->Cell($w[0], self::ALTO_FILA, $esPago ? '' : $this->truncar((string) ($fila->nombre ?? ''), $maxChars[0]), 1, 0, 'L');
            $this->Cell($w[1], self::ALTO_FILA, $esPago ? '' : $this->truncar((string) ($fila->nombreProtocolo ?? ''), $maxChars[1]), 1, 0, 'C');
            $this->Cell($w[2], self::ALTO_FILA, $esPago ? '' : $this->truncar((string) ($fila->especie ?? ''), $maxChars[2]), 1, 0, 'C');
            $this->Cell($w[3], self::ALTO_FILA, $esPago ? '' : $this->truncar((string) ($fila->raza ?? ''), $maxChars[3]), 1, 0, 'L');
            $this->Cell($w[4], self::ALTO_FILA, $fecha, 1, 0, 'C');
            $this->Cell($w[5], self::ALTO_FILA, $esPago ? '' : $this->truncar((string) ($fila->propietario ?? ''), $maxChars[5]), 1, 0, 'L');
            $this->Cell($w[6], self::ALTO_FILA, $this->truncar((string) ($fila->estado ?? ''), $maxChars[6]), 1, 0, 'C');
            $this->Cell($w[7], self::ALTO_FILA, CuentaCorrienteConsulta::formatearMoneda((float) ($fila->precio ?? 0)), 1, 0, 'R');
            $this->Cell($w[8], self::ALTO_FILA, CuentaCorrienteConsulta::formatearMoneda((float) ($fila->descuento ?? 0)), 1, 0, 'R');
            $this->Cell($w[9], self::ALTO_FILA, CuentaCorrienteConsulta::formatearMoneda((float) ($fila->pagado ?? 0)), 1, 0, 'R');
            $this->Cell($w[10], self::ALTO_FILA, CuentaCorrienteConsulta::formatearMoneda((float) ($fila->saldo ?? 0)), 1, 1, 'R');
        }
    }

    /**
     * @return list<float>
     */
    private function anchosColumnas(): array
    {
        $anchoUtil = $this->getPageWidth() - (self::MARGEN * 2);

        $fijos = [
            26.0, // Nombre
            22.0, // Protocolo
            20.0, // Especie
            30.0, // Raza
            22.0, // Fecha
            18.0, // Estado
            22.0, // Precio
            22.0, // Descuento
            22.0, // Pagado
            22.0, // Saldo
        ];
        $propietario = max(28.0, $anchoUtil - array_sum($fijos));

        return [
            $fijos[0], $fijos[1], $fijos[2], $fijos[3], $fijos[4],
            $propietario,
            $fijos[5], $fijos[6], $fijos[7], $fijos[8], $fijos[9],
        ];
    }

    /**
     * @param  list<float>  $anchos
     * @return list<int>
     */
    private function maximosCaracteresColumna(array $anchos): array
    {
        return array_map(fn (float $ancho) => max(4, (int) floor($ancho / 1.6)), $anchos);
    }

    private function truncar(string $texto, int $maximo): string
    {
        return mb_substr($texto, 0, $maximo);
    }
}
