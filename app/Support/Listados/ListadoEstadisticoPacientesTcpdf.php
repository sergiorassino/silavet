<?php

namespace App\Support\Listados;

use App\Support\Pdf\TcpdfFuenteArial;
use App\Support\Pdf\TcpdfHeaderInstitucional;
use Carbon\Carbon;
use TCPDF;

/**
 * Listado estadístico de pacientes — TCPDF vertical A4.
 */
final class ListadoEstadisticoPacientesTcpdf extends TCPDF
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
        $this->SetTitle('Listado estadístico de pacientes');
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
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Content-Disposition' => 'inline; filename="'.$nombreArchivo.'"',
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
            ['Listado estadístico de pacientes', 'B', 11],
            ['Período: '.(string) ($this->datos['periodo_texto'] ?? 'Todo el historial'), '', 8],
        ]);
        $this->SetY($y);

        $w = $this->anchosColumnas();
        $titulos = ['#', 'Clientes', 'Especies', 'Razas', 'Fecha', 'Protocolo', 'Nombre', 'Propietario', 'Estado', 'Precio', 'Pagado'];
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
        $maxChars = $this->maximosCaracteresColumna($w);
        $numero = 1;
        $agrupar = (bool) ($this->datos['agruparPorCliente'] ?? false);

        /** @var list<object> $filas */
        $filas = (array) ($this->datos['filas'] ?? []);

        if ($agrupar) {
            $bloques = ListadoEstadisticoPacientesConsulta::bloquesAgrupados($filas);
            foreach ($bloques as $bloque) {
                if ($bloque['tipo'] === 'grupo') {
                    TcpdfFuenteArial::aplicar($this, 'B', 5);
                    $this->SetFillColor(220, 230, 232);
                    $anchoEtiqueta = array_sum(array_slice($w, 0, 9));
                    $etiqueta = (string) ($bloque['cliente'] ?? 'Sin cliente')
                        .' ('.(int) ($bloque['cantidad'] ?? 0).')';
                    $this->Cell($anchoEtiqueta, 3.5, $this->truncar($etiqueta, (int) floor($anchoEtiqueta / 1.7)), 1, 0, 'L', true);
                    $this->Cell($w[9], 3.5, ListadoEstadisticoPacientesConsulta::formatearMoneda((float) ($bloque['subtotal_precio'] ?? 0)), 1, 0, 'R', true);
                    $this->Cell($w[10], 3.5, ListadoEstadisticoPacientesConsulta::formatearMoneda((float) ($bloque['subtotal_pagado'] ?? 0)), 1, 1, 'R', true);
                    $this->SetFillColor(255, 255, 255);
                    TcpdfFuenteArial::aplicar($this, '', 5);
                    continue;
                }

                $this->dibujarFilaDatos($bloque['fila'], $numero, $w, $maxChars);
                $numero++;
            }
        } else {
            foreach ($filas as $fila) {
                $this->dibujarFilaDatos($fila, $numero, $w, $maxChars);
                $numero++;
            }
        }

        $this->Ln(2);
        TcpdfFuenteArial::aplicar($this, 'B', 7);
        $anchoEtiqueta = array_sum(array_slice($w, 0, 9));
        $this->Cell($anchoEtiqueta, 4, 'Total:', 0, 0, 'R');
        $this->Cell($w[9], 4, ListadoEstadisticoPacientesConsulta::formatearMoneda((float) ($this->datos['total_precio'] ?? 0)), 0, 0, 'R');
        $this->Cell($w[10], 4, ListadoEstadisticoPacientesConsulta::formatearMoneda((float) ($this->datos['total_pagado'] ?? 0)), 0, 1, 'R');
    }

    /**
     * @param  list<float>  $w
     * @param  list<int>  $maxChars
     */
    private function dibujarFilaDatos(object $fila, int $numero, array $w, array $maxChars): void
    {
        $fecha = $fila->fechhoy !== '' ? Carbon::parse($fila->fechhoy)->format('d/m/Y') : '';
        $this->Cell($w[0], 3.5, (string) $numero, 1, 0, 'C');
        $this->Cell($w[1], 3.5, $this->truncar((string) ($fila->cliente ?? ''), $maxChars[1]), 1, 0, 'L');
        $this->Cell($w[2], 3.5, $this->truncar((string) ($fila->especie ?? ''), $maxChars[2]), 1, 0, 'L');
        $this->Cell($w[3], 3.5, $this->truncar((string) ($fila->raza ?? ''), $maxChars[3]), 1, 0, 'L');
        $this->Cell($w[4], 3.5, $fecha, 1, 0, 'C');
        $this->Cell($w[5], 3.5, $this->truncar((string) ($fila->nombreProtocolo ?? ''), $maxChars[5]), 1, 0, 'L');
        $this->Cell($w[6], 3.5, $this->truncar((string) ($fila->nombre ?? ''), $maxChars[6]), 1, 0, 'L');
        $this->Cell($w[7], 3.5, $this->truncar((string) ($fila->propietario ?? ''), $maxChars[7]), 1, 0, 'L');
        $this->Cell($w[8], 3.5, $this->truncar((string) ($fila->estado ?? ''), $maxChars[8]), 1, 0, 'C');
        $this->Cell($w[9], 3.5, ListadoEstadisticoPacientesConsulta::formatearMoneda((float) ($fila->precio ?? 0)), 1, 0, 'R');
        $this->Cell($w[10], 3.5, ListadoEstadisticoPacientesConsulta::formatearMoneda((float) ($fila->pagado ?? 0)), 1, 1, 'R');
    }

    /**
     * @return list<float>
     */
    private function anchosColumnas(): array
    {
        $anchoUtil = $this->getPageWidth() - (self::MARGEN * 2);
        $wNum = 6.0;
        $wEspecie = 12.0;
        $wRaza = 14.0;
        $wFecha = 14.0;
        $wProtocolo = 16.0;
        $wEstado = 12.0;
        $wImporte = 14.0;
        $wCliente = 22.0;
        $wNombre = 16.0;
        $wPropietario = max(12.0, $anchoUtil - ($wNum + $wCliente + $wEspecie + $wRaza + $wFecha + $wProtocolo + $wNombre + $wEstado + (2 * $wImporte)));

        return [$wNum, $wCliente, $wEspecie, $wRaza, $wFecha, $wProtocolo, $wNombre, $wPropietario, $wEstado, $wImporte, $wImporte];
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
