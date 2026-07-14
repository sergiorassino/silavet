<?php

namespace App\Support\Listados;

use App\Support\Pdf\TcpdfFuenteArial;
use App\Support\Pdf\TcpdfHeaderInstitucional;
use Carbon\Carbon;
use TCPDF;

/**
 * Historial de determinaciones — TCPDF vertical A4.
 */
final class HistorialDeterminacionesTcpdf extends TCPDF
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
        $this->SetTitle('Historial de determinaciones');
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
            ['Historial de determinaciones', 'B', 11],
            ['Período: '.(string) ($this->datos['periodo_texto'] ?? 'Todo el historial'), '', 8],
        ]);
        $this->SetY($y);

        $w = $this->anchosColumnas();
        $titulos = ['Fecha', 'Cliente', 'Protocolo', 'Paciente', 'Especie', 'Grupo', 'Determinación', 'Valor'];
        TcpdfFuenteArial::aplicar($this, 'B', 6);
        $this->SetFillColor(193, 215, 218);
        $this->SetTextColor(51, 51, 51);
        foreach ($titulos as $i => $titulo) {
            $this->Cell($w[$i], 4.5, $titulo, 1, 0, 'C', true);
        }
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0, 0, 0);
        $this->Ln();

        TcpdfFuenteArial::aplicar($this, '', 5.5);
        $maxChars = $this->maximosCaracteresColumna($w);

        /** @var list<object> $filas */
        $filas = (array) ($this->datos['filas'] ?? []);

        foreach ($filas as $fila) {
            $this->dibujarFilaDatos($fila, $w, $maxChars);
        }

        $this->Ln(2);
        TcpdfFuenteArial::aplicar($this, 'B', 7);
        $this->Cell($anchoUtil, 4, 'Total: '.count($filas).' registros', 0, 1, 'R');
    }

    /**
     * @param  list<float>  $w
     * @param  list<int>  $maxChars
     */
    private function dibujarFilaDatos(object $fila, array $w, array $maxChars): void
    {
        $fecha = $fila->fechhoy !== '' ? Carbon::parse($fila->fechhoy)->format('d/m/Y') : '';
        $this->Cell($w[0], 3.5, $fecha, 1, 0, 'C');
        $this->Cell($w[1], 3.5, $this->truncar((string) ($fila->cliente ?? ''), $maxChars[1]), 1, 0, 'L');
        $this->Cell($w[2], 3.5, $this->truncar((string) ($fila->protocolo ?? ''), $maxChars[2]), 1, 0, 'L');
        $this->Cell($w[3], 3.5, $this->truncar((string) ($fila->paciente ?? ''), $maxChars[3]), 1, 0, 'L');
        $this->Cell($w[4], 3.5, $this->truncar((string) ($fila->especie ?? ''), $maxChars[4]), 1, 0, 'L');
        $this->Cell($w[5], 3.5, $this->truncar(mb_strtoupper((string) ($fila->grupo ?? '')), $maxChars[5]), 1, 0, 'L');
        $this->Cell($w[6], 3.5, $this->truncar((string) ($fila->determinacion ?? ''), $maxChars[6]), 1, 0, 'L');
        $this->Cell($w[7], 3.5, $this->truncar((string) ($fila->valor ?? ''), $maxChars[7]), 1, 1, 'R');
    }

    /**
     * @return list<float>
     */
    private function anchosColumnas(): array
    {
        $anchoUtil = $this->getPageWidth() - (self::MARGEN * 2);
        $wFecha = 16.0;
        $wCliente = 28.0;
        $wProtocolo = 18.0;
        $wPaciente = 22.0;
        $wEspecie = 16.0;
        $wGrupo = 28.0;
        $wValor = 16.0;
        $wDeterminacion = max(20.0, $anchoUtil - ($wFecha + $wCliente + $wProtocolo + $wPaciente + $wEspecie + $wGrupo + $wValor));

        return [$wFecha, $wCliente, $wProtocolo, $wPaciente, $wEspecie, $wGrupo, $wDeterminacion, $wValor];
    }

    /**
     * @param  list<float>  $anchos
     * @return list<int>
     */
    private function maximosCaracteresColumna(array $anchos): array
    {
        return array_map(fn (float $ancho) => max(4, (int) floor($ancho / 1.65)), $anchos);
    }

    private function truncar(string $texto, int $maximo): string
    {
        return mb_substr($texto, 0, $maximo);
    }
}
