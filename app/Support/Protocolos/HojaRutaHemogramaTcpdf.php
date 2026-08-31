<?php

namespace App\Support\Protocolos;

use App\Support\Pdf\TcpdfFuenteArial;
use Illuminate\Http\Response;
use TCPDF;

/**
 * Hoja de ruta (hemograma) — planilla de trabajo A4 vertical.
 *
 * Planilla A4 vertical de hemograma del día: fecha + paginado, un bloque
 * por protocolo, celdas en blanco para anotar valores. Sin membrete
 * institucional y sin columna de citologías.
 */
final class HojaRutaHemogramaTcpdf extends TCPDF
{
    private const MARGEN = 5.0;

    private const ALTO = 6.0;

    /** Columna única de identificación (ex Protocolo + Paciente + Esp). */
    private const ANCHO_ID = 34.0;

    /**
     * Ancho de cada columna de determinación.
     * 16 cols × 10.375 = 166 mm: se reparte el recorte de 10 mm de la
     * columna de identificación (planilla 200 mm).
     */
    private const ANCHO_COL = 10.375;

    private const ANCHO_HEMO_ETIQUETA = 68.0;

    /** Ancho total del bloque: 34 (id) + 166 (grilla) = 200 mm. */
    private const ANCHO_BLOQUE = 200.0;

    private const FILAS_GRILLA = 3;

    private const MAX_POR_PAGINA = 9;

    private const GAP_BLOQUE = 2.0;

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
        $this->SetTitle('Hoja de ruta (Hemograma)');
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetAutoPageBreak(false);
        $this->SetMargins(self::MARGEN, self::MARGEN, self::MARGEN);
    }

    /**
     * @param  array{
     *     fecha_texto?: string,
     *     filas?: list<array{
     *         nombreProtocolo?: string,
     *         nombre?: string,
     *         especie?: string,
     *         raza?: string,
     *         sexo?: string,
     *         edad?: string,
     *         cliente?: string,
     *         detPedidas?: list<int>
     *     }>
     * }  $datos
     */
    public static function generar(array $datos): self
    {
        $pdf = new self($datos);
        $pdf->AddPage();
        $pdf->dibujar();

        return $pdf;
    }

    public static function nombreArchivo(string $fecha): string
    {
        $fecha = preg_replace('/[^0-9-]/', '', $fecha) ?: now()->toDateString();

        return 'hoja-ruta-hemograma-'.$fecha.'.pdf';
    }

    public static function respuestaHttp(self $pdf, string $nombreArchivo): Response
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
        $fechaTexto = (string) ($this->datos['fecha_texto'] ?? '');
        /** @var list<array<string, mixed>> $filas */
        $filas = (array) ($this->datos['filas'] ?? []);

        $this->dibujarEncabezadoPagina($fechaTexto, 1);

        $i = 0;
        foreach ($filas as $fila) {
            if ($i > 0 && ($i % self::MAX_POR_PAGINA) === 0) {
                $this->AddPage();
                $this->dibujarEncabezadoPagina($fechaTexto, intdiv($i, self::MAX_POR_PAGINA) + 1);
            }
            $this->dibujarBloquePaciente($fila);
            $i++;
        }
    }

    private function dibujarEncabezadoPagina(string $fechaTexto, int $pagina): void
    {
        TcpdfFuenteArial::aplicar($this, 'B', 10);
        $this->SetXY(0, 2);
        $this->Cell(180, 4, 'Fecha: '.$fechaTexto, 0, 1, 'C');
        TcpdfFuenteArial::aplicar($this, 'I', 8);
        $this->SetXY(-30, 2);
        $this->Cell(25, 4, 'Pag '.$pagina, 0, 1, 'R');
        $this->SetY(8);
    }

    /**
     * @param  array<string, mixed>  $fila
     */
    private function dibujarBloquePaciente(array $fila): void
    {
        /** @var list<int> $pedidos */
        $pedidos = array_map('intval', (array) ($fila['detPedidas'] ?? []));
        $columnas = HojaRutaHemogramaConfig::columnas();

        $x0 = $this->GetX();
        $y0 = $this->GetY();
        $altoId = self::ALTO * self::FILAS_GRILLA;

        $this->dibujarGrillaDeterminaciones($x0 + self::ANCHO_ID, $y0, $columnas, $pedidos);
        $this->dibujarColumnaIdentificacion($x0, $y0, $fila, $altoId);

        $this->SetXY($x0, $y0 + $altoId);
        $this->dibujarFilaObs();
        $this->dibujarFilaHemoparasitos($pedidos);
        $this->Ln(self::GAP_BLOQUE);
    }

    /**
     * @param  array<string, mixed>  $fila
     */
    private function dibujarColumnaIdentificacion(float $x, float $y, array $fila, float $alto): void
    {
        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor(0, 0, 0);
        $this->Rect($x, $y, self::ANCHO_ID, $alto, 'DF');

        $padX = 1.2;
        $padY = 0.5;
        $maxW = self::ANCHO_ID - (2 * $padX);
        $limiteY = $y + $alto - 0.4;
        $cursorY = $y + $padY;

        $lineas = HojaRutaHemogramaConsulta::lineasIdentificacion($fila);
        foreach ($lineas as $i => $linea) {
            $esTitulo = $i <= 1;
            $size = $i === 0 ? 7.0 : 6.5;
            $style = $esTitulo ? 'B' : '';
            $lh = 3.4;
            if ($cursorY + $lh > $limiteY) {
                break;
            }
            $texto = $this->truncar($linea, $maxW, $style, $size);
            TcpdfFuenteArial::aplicar($this, $style, $size);
            $this->SetXY($x + $padX, $cursorY);
            $this->Cell($maxW, $lh, $texto, 0, 0, 'L', false);
            $cursorY += $lh;
        }
    }

    /**
     * @param  list<array{clave: string, titulo: string, encabezado: string, id: int}>  $columnas
     * @param  list<int>  $pedidos
     */
    private function dibujarGrillaDeterminaciones(float $x, float $y, array $columnas, array $pedidos): void
    {
        $this->SetXY($x, $y);
        $this->dibujarFilaTitulos($columnas);
        $this->SetX($x);
        $this->dibujarFilaValores($columnas, $pedidos);
        $this->SetX($x);
        $this->dibujarFilaValores($columnas, $pedidos);
    }

    /**
     * @param  list<array{clave: string, titulo: string, encabezado: string, id: int}>  $columnas
     */
    private function dibujarFilaTitulos(array $columnas): void
    {
        TcpdfFuenteArial::aplicar($this, 'B', 7);
        foreach ($columnas as $col) {
            if ($col['encabezado'] === HojaRutaHemogramaConfig::ENCABEZADO_AZUL) {
                $this->SetFillColor(200, 220, 255);
            } else {
                $this->SetFillColor(255, 255, 150);
            }
            $this->Cell(self::ANCHO_COL, self::ALTO, $col['titulo'], 1, 0, 'C', true);
        }
        $this->Ln();
    }

    /**
     * @param  list<array{clave: string, titulo: string, encabezado: string, id: int}>  $columnas
     * @param  list<int>  $pedidos
     */
    private function dibujarFilaValores(array $columnas, array $pedidos): void
    {
        TcpdfFuenteArial::aplicar($this, '', 7);
        foreach ($columnas as $col) {
            $fill = $this->aplicarFondoPedido(HojaRutaHemogramaConfig::itemPedido($col['id'], $pedidos));
            $this->Cell(self::ANCHO_COL, self::ALTO, '', 1, 0, 'C', $fill);
        }
        $this->Ln();
    }

    /**
     * @param  list<int>  $pedidos
     */
    private function dibujarFilaHemoparasitos(array $pedidos): void
    {
        TcpdfFuenteArial::aplicar($this, '', 7);
        $fill = $this->aplicarFondoPedido(
            HojaRutaHemogramaConfig::itemPedido(HojaRutaHemogramaConfig::idEspecial('hemoparasitos'), $pedidos)
        );
        $anchoValor = self::ANCHO_BLOQUE - self::ANCHO_HEMO_ETIQUETA;
        $this->Cell(self::ANCHO_HEMO_ETIQUETA, self::ALTO, 'Hemoparásitos:', 1, 0, 'L', $fill);
        $this->Cell($anchoValor, self::ALTO, '', 1, 1, 'C', $fill);
    }

    private function dibujarFilaObs(): void
    {
        TcpdfFuenteArial::aplicar($this, '', 7);
        $this->SetFillColor(255, 255, 255);
        $this->Cell(self::ANCHO_BLOQUE, self::ALTO, 'Obs:', 1, 1, 'L', false);
    }

    private function aplicarFondoPedido(bool $pedido): int
    {
        if ($pedido) {
            $this->SetFillColor(255, 255, 255);

            return 0;
        }

        $this->SetFillColor(220, 220, 220);

        return 1;
    }

    private function truncar(string $texto, float $maxW, string $style, float $size): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        TcpdfFuenteArial::aplicar($this, $style, $size);
        if ($this->GetStringWidth($texto) <= $maxW) {
            return $texto;
        }

        $ellipsis = '…';
        while ($texto !== '' && $this->GetStringWidth($texto.$ellipsis) > $maxW) {
            if (function_exists('mb_substr')) {
                $texto = mb_substr($texto, 0, max(0, mb_strlen($texto, 'UTF-8') - 1), 'UTF-8');
            } else {
                $texto = substr($texto, 0, max(0, strlen($texto) - 1));
            }
        }

        return $texto === '' ? $ellipsis : $texto.$ellipsis;
    }
}
