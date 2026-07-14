<?php

namespace App\Support\Listados;

use App\Support\Pdf\TcpdfFuenteArial;
use App\Support\Pdf\TcpdfHeaderInstitucional;
use TCPDF;

/**
 * Comparativa cantidad de determinaciones (tabla) — TCPDF A4 vertical.
 */
final class CantidadDeterminacionesComparacTcpdf extends TCPDF
{
    private const MARGEN = 10.0;

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
        $this->SetTitle('Cantidad determinaciones (comparac.)');
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

        $periodo1 = (string) ($this->datos['periodo1'] ?? '—');
        $periodo2 = (string) ($this->datos['periodo2'] ?? '—');

        $y = TcpdfHeaderInstitucional::dibujarLineasCentradas($this, $y, [
            ['Cantidad de determinaciones (comparativa)', 'B', 11],
            ['Período 1: '.$periodo1, '', 8],
            ['Período 2: '.$periodo2, '', 8],
        ]);
        $this->SetY($y + 2);

        $w = [70.0, 35.0, 35.0, 35.0];
        $titulos = ['Determinación', 'Período 1', 'Período 2', 'Diferencia'];
        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $this->SetFillColor(193, 215, 218);
        $this->SetTextColor(51, 51, 51);
        foreach ($titulos as $i => $titulo) {
            $this->Cell($w[$i], 6, $titulo, 1, 0, 'C', true);
        }
        $this->Ln();

        TcpdfFuenteArial::aplicar($this, '', 7.5);
        $this->SetTextColor(0, 0, 0);

        /** @var list<object> $filas */
        $filas = (array) ($this->datos['filas'] ?? []);
        foreach ($filas as $fila) {
            $nombre = $this->truncar((string) ($fila->nombre ?? ''), 48);
            $this->Cell($w[0], 5, $nombre, 1, 0, 'L');
            $this->Cell($w[1], 5, (string) (int) ($fila->cantidad1 ?? 0), 1, 0, 'C');
            $this->Cell($w[2], 5, (string) (int) ($fila->cantidad2 ?? 0), 1, 0, 'C');
            $diff = (int) ($fila->diferencia ?? 0);
            $this->Cell($w[3], 5, ($diff > 0 ? '+' : '').(string) $diff, 1, 0, 'C');
            $this->Ln();
        }

        $this->Ln(2);
        TcpdfFuenteArial::aplicar($this, 'B', 8);
        $total1 = (int) ($this->datos['total1'] ?? 0);
        $total2 = (int) ($this->datos['total2'] ?? 0);
        $totalDiff = (int) ($this->datos['totalDiferencia'] ?? ($total2 - $total1));

        $this->Cell($w[0], 6, 'Totales', 1, 0, 'R', false);
        $this->Cell($w[1], 6, (string) $total1, 1, 0, 'C');
        $this->Cell($w[2], 6, (string) $total2, 1, 0, 'C');
        $this->Cell($w[3], 6, ($totalDiff > 0 ? '+' : '').(string) $totalDiff, 1, 1, 'C');
    }

    private function truncar(string $texto, int $max): string
    {
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }

        return mb_substr($texto, 0, $max - 1).'…';
    }
}
