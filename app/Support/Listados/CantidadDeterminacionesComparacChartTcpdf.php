<?php

namespace App\Support\Listados;

use App\Support\Pdf\TcpdfFuenteArial;
use App\Support\Pdf\TcpdfHeaderInstitucional;
use TCPDF;

/**
 * Comparativa cantidad de determinaciones (gráfico) — TCPDF A4 vertical.
 * Recibe imagen PNG/JPEG del canvas del navegador.
 */
final class CantidadDeterminacionesComparacChartTcpdf extends TCPDF
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
        $this->SetTitle('Gráfico comparativa determinaciones');
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
            ['Comparativa entre los períodos', 'B', 11],
            [$periodo1, '', 8],
            [$periodo2, '', 8],
        ]);
        $this->SetY($y + 3);

        $imagenB64 = (string) ($this->datos['chart_base64'] ?? '');
        if ($imagenB64 === '') {
            TcpdfFuenteArial::aplicar($this, '', 10);
            $this->MultiCell($anchoUtil, 6, 'No se recibió la imagen del gráfico.', 0, 'C');

            return;
        }

        if (str_starts_with($imagenB64, 'data:image/')) {
            $partes = explode(',', $imagenB64, 2);
            $imagenB64 = $partes[1] ?? '';
        }

        $binario = base64_decode($imagenB64, true);
        if ($binario === false || $binario === '') {
            TcpdfFuenteArial::aplicar($this, '', 10);
            $this->MultiCell($anchoUtil, 6, 'La imagen del gráfico no es válida.', 0, 'C');

            return;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'vlchart_');
        if ($tmp === false) {
            TcpdfFuenteArial::aplicar($this, '', 10);
            $this->MultiCell($anchoUtil, 6, 'No se pudo preparar la imagen del gráfico.', 0, 'C');

            return;
        }

        $rutaPng = $tmp.'.png';
        @rename($tmp, $rutaPng);
        file_put_contents($rutaPng, $binario);

        try {
            $yImg = $this->GetY();
            $altoDisponible = max(40.0, $this->getPageHeight() - $yImg - self::MARGEN - 5);
            // Ancho completo; alto 0 = proporción. Si supera el alto disponible, TCPDF reduce con fitonpage.
            $this->Image(
                $rutaPng,
                self::MARGEN,
                $yImg,
                $anchoUtil,
                0,
                'PNG',
                '',
                '',
                false,
                150,
                '',
                false,
                false,
                0,
                true,
            );
            unset($altoDisponible);
        } finally {
            @unlink($rutaPng);
        }
    }
}
