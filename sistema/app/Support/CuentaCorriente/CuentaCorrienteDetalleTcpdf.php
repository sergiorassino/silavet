<?php

namespace App\Support\CuentaCorriente;

use App\Support\Pdf\TcpdfFuenteArial;
use App\Support\Pdf\TcpdfHeaderInstitucional;
use Carbon\Carbon;
use TCPDF;

/**
 * Detalle de cuenta corriente por cliente — TCPDF vertical A4.
 */
final class CuentaCorrienteDetalleTcpdf extends TCPDF
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
        $titulos = ['#', 'Paciente', 'Cliente', 'Especie', 'Raza', 'Fecha', 'Protocolo', 'Nombre', 'Propietario', 'Estado', 'Precio', 'Pagado', 'Saldo'];
        TcpdfFuenteArial::aplicar($this, 'B', 5.5);
        $this->SetFillColor(193, 215, 218);
        $this->SetTextColor(51, 51, 51);
        foreach ($titulos as $i => $titulo) {
            $this->Cell($w[$i], 4.5, $titulo, 1, 0, 'C', true);
        }
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0, 0, 0);
        $this->Ln();

        TcpdfFuenteArial::aplicar($this, '', 5);
        /** @var list<object> $filas */
        $filas = (array) ($this->datos['filas'] ?? []);
        $numero = 1;
        $maxChars = $this->maximosCaracteresColumna($w);
        foreach ($filas as $fila) {
            $fecha = $fila->fechhoy !== '' ? Carbon::parse($fila->fechhoy)->format('d/m/Y') : '';
            $this->Cell($w[0], 3.5, (string) $numero, 1, 0, 'C');
            $this->Cell($w[1], 3.5, $this->truncar((string) ($fila->nombre ?? ''), $maxChars[1]), 1, 0, 'L');
            $this->Cell($w[2], 3.5, (string) ($fila->idClientes ?? ''), 1, 0, 'C');
            $this->Cell($w[3], 3.5, $this->truncar((string) ($fila->especie ?? ''), $maxChars[3]), 1, 0, 'L');
            $this->Cell($w[4], 3.5, $this->truncar((string) ($fila->raza ?? ''), $maxChars[4]), 1, 0, 'L');
            $this->Cell($w[5], 3.5, $fecha, 1, 0, 'C');
            $this->Cell($w[6], 3.5, $this->truncar((string) ($fila->nombreProtocolo ?? ''), $maxChars[6]), 1, 0, 'L');
            $this->Cell($w[7], 3.5, $this->truncar((string) ($fila->nombre ?? ''), $maxChars[7]), 1, 0, 'L');
            $this->Cell($w[8], 3.5, $this->truncar((string) ($fila->propietario ?? ''), $maxChars[8]), 1, 0, 'L');
            $this->Cell($w[9], 3.5, $this->truncar((string) ($fila->estado ?? ''), $maxChars[9]), 1, 0, 'C');
            $this->Cell($w[10], 3.5, CuentaCorrienteConsulta::formatearMoneda((float) ($fila->precio ?? 0)), 1, 0, 'R');
            $this->Cell($w[11], 3.5, CuentaCorrienteConsulta::formatearMoneda((float) ($fila->pagado ?? 0)), 1, 0, 'R');
            $this->Cell($w[12], 3.5, CuentaCorrienteConsulta::formatearMoneda((float) ($fila->saldo ?? 0)), 1, 1, 'R');
            $numero++;
        }

        $saldoAnterior = $this->datos['saldo_anterior'] ?? null;
        $fechaDesde = trim((string) ($this->datos['fecha_desde'] ?? ''));
        if ($saldoAnterior !== null && $fechaDesde !== '') {
            TcpdfFuenteArial::aplicar($this, 'B', 5);
            $anchoEtiqueta = array_sum(array_slice($w, 0, 12));
            $this->Cell($anchoEtiqueta, 3.5, 'Saldo anterior al '.Carbon::parse($fechaDesde)->format('d/m/Y'), 1, 0, 'R', true);
            $this->Cell($w[12], 3.5, CuentaCorrienteConsulta::formatearMoneda((float) $saldoAnterior), 1, 1, 'R', true);
            TcpdfFuenteArial::aplicar($this, '', 5);
        }

        $this->Ln(2);
        TcpdfFuenteArial::aplicar($this, 'B', 7);
        $anchoEtiqueta = array_sum(array_slice($w, 0, 10));
        $this->Cell($anchoEtiqueta, 4, 'Total:', 0, 0, 'R');
        $this->Cell($w[10], 4, CuentaCorrienteConsulta::formatearMoneda((float) ($this->datos['total_precio'] ?? 0)), 0, 0, 'R');
        $this->Cell($w[11], 4, CuentaCorrienteConsulta::formatearMoneda((float) ($this->datos['total_pagado'] ?? 0)), 0, 0, 'R');
        $this->Cell($w[12], 4, '', 0, 1, 'R');
    }

    /**
     * @return list<float>
     */
    private function anchosColumnas(): array
    {
        $anchoUtil = $this->getPageWidth() - (self::MARGEN * 2);
        $wNum = 5.0;
        $wIdCliente = 8.0;
        $wFecha = 13.0;
        $wProtocolo = 13.0;
        $wEstado = 10.0;
        $wImporte = 13.0;
        $wEspecie = 10.0;
        $wRaza = 9.0;
        $wPaciente = 12.0;
        $wNombre = 11.0;
        $wPropietario = max(12.0, $anchoUtil - ($wNum + $wPaciente + $wIdCliente + $wEspecie + $wRaza + $wFecha + $wProtocolo + $wNombre + $wEstado + (3 * $wImporte)));

        return [$wNum, $wPaciente, $wIdCliente, $wEspecie, $wRaza, $wFecha, $wProtocolo, $wNombre, $wPropietario, $wEstado, $wImporte, $wImporte, $wImporte];
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
