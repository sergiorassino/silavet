<?php

namespace App\Support\Listados;

use App\Support\Pdf\TcpdfFuenteArial;
use App\Support\Pdf\TcpdfHeaderInstitucional;
use Carbon\Carbon;
use TCPDF;

/**
 * Clientes resumen mensual — TCPDF.
 *
 * Orientación horizontal (A4 landscape) explícita: replica el blank ScriptCase
 * pacientesIVApdf (10 columnas + mini-tabla de determinaciones).
 */
final class ClientesResumenMensualTcpdf extends TCPDF
{
    private const MARGEN = 8.0;

    private const ALTO_ENCABEZADO = 6.0;

    private const ALTO_GRUPO = 5.5;

    private const ALTO_LINEA_DET = 3.4;

    private const ALTO_FILA_MIN = 7.0;

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
        $this->SetTitle('Clientes resumen mensual');
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetAutoPageBreak(false, 8);
        $this->SetMargins(self::MARGEN, self::MARGEN, self::MARGEN);
        $this->SetDrawColor(197, 205, 216);
        $this->SetLineWidth(0.15);
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
        $info = (array) ($this->datos['info_cliente'] ?? []);

        $y = TcpdfHeaderInstitucional::dibujar(
            $this,
            self::MARGEN,
            self::MARGEN,
            $anchoUtil,
            $header,
        );

        $y = TcpdfHeaderInstitucional::dibujarLineasCentradas($this, $y, [
            ['Clientes resumen mensual', 'B', 12],
            [
                'Periodo: '.(string) ($this->datos['periodo_texto'] ?? '')
                .'  |  Cliente: '.(string) ($info['nombre'] ?? 'Todos los clientes'),
                '',
                8,
            ],
        ]);
        $this->SetY($y + 1.5);

        /** @var list<object> $filas */
        $filas = (array) ($this->datos['filas'] ?? []);
        if ($filas === []) {
            TcpdfFuenteArial::aplicar($this, 'I', 10);
            $this->Cell(0, 8, (string) ($this->datos['mensaje_vacio'] ?? 'Sin datos.'), 0, 1, 'C');

            return;
        }

        $this->dibujarEncabezados();
        $bloques = ClientesResumenMensualConsulta::bloquesAgrupados($filas);
        foreach ($bloques as $bloque) {
            if ($bloque['tipo'] === 'grupo') {
                $this->dibujarGrupo((string) $bloque['cliente']);
                continue;
            }
            if ($bloque['tipo'] === 'subtotal') {
                $this->dibujarSubtotal($bloque);
                continue;
            }
            $this->dibujarFilaDatos($bloque['fila']);
        }

        $totales = (array) ($this->datos['totales'] ?? []);
        $this->dibujarTotal(
            'Total general ('.(int) ($totales['cantidad'] ?? 0).' pac.)',
            (float) ($totales['sum_sin_iva'] ?? 0),
            (float) ($totales['sum_iva'] ?? 0),
            (float) ($totales['sum_con_iva'] ?? 0),
            (float) ($totales['sum_pagado'] ?? 0),
            [238, 242, 255],
            [184, 223, 245],
        );

        if (! empty($info['mostrar'])) {
            $this->dibujarDescuento($info, $totales);
        }
    }

    /**
     * @return list<float>
     */
    private function anchos(): array
    {
        $util = $this->getPageWidth() - (2 * self::MARGEN);
        $fijos = [16.0, 38.0, 20.0, 32.0, 18.0, 18.0, 20.0, 18.0, 22.0];
        $det = max(40.0, $util - array_sum($fijos));

        return [...$fijos, $det];
    }

    private function dibujarEncabezados(): void
    {
        $this->asegurarEspacio(self::ALTO_ENCABEZADO + 1);
        $w = $this->anchos();
        $titulos = [
            'Fecha', 'Cliente', 'Protocolo', 'Paciente',
            'Neto s/IVA', 'IVA', 'Precio c/IVA', 'Pagado', 'Medio pago', 'Determinaciones',
        ];
        $x = self::MARGEN;
        $y = $this->GetY();
        TcpdfFuenteArial::aplicar($this, 'B', 6.5);
        foreach ($titulos as $i => $titulo) {
            $fill = ($i >= 4 && $i <= 6) ? [184, 223, 245] : [232, 237, 243];
            $align = ($i >= 4 && $i <= 7) ? 'R' : (($i === 9) ? 'C' : 'L');
            $this->celda($x, $y, $w[$i], self::ALTO_ENCABEZADO, $titulo, $align, $fill, true);
            $x += $w[$i];
        }
        $this->SetY($y + self::ALTO_ENCABEZADO);
    }

    private function dibujarGrupo(string $cliente): void
    {
        $this->asegurarEspacio(self::ALTO_GRUPO + 1);
        $w = $this->anchos();
        $y = $this->GetY();
        $this->celda(self::MARGEN, $y, array_sum($w), self::ALTO_GRUPO, $cliente, 'L', [220, 227, 236], true);
        $this->SetY($y + self::ALTO_GRUPO);
    }

    /**
     * @param  array<string, mixed>  $bloque
     */
    private function dibujarSubtotal(array $bloque): void
    {
        $this->dibujarTotal(
            'Subtotal '.(string) ($bloque['cliente'] ?? '').' ('.(int) ($bloque['cantidad'] ?? 0).' pac.)',
            (float) ($bloque['sum_sin_iva'] ?? 0),
            (float) ($bloque['sum_iva'] ?? 0),
            (float) ($bloque['sum_con_iva'] ?? 0),
            (float) ($bloque['sum_pagado'] ?? 0),
            [243, 244, 246],
            [168, 216, 240],
        );
    }

    private function dibujarTotal(
        string $etiqueta,
        float $sinIva,
        float $iva,
        float $conIva,
        float $pagado,
        array $fillBase,
        array $fillIva,
    ): void {
        $this->asegurarEspacio(self::ALTO_ENCABEZADO + 1);
        $w = $this->anchos();
        $y = $this->GetY();
        $x = self::MARGEN;
        $h = self::ALTO_ENCABEZADO;
        $fmt = [ClientesResumenMensualConsulta::formatearMoneda($sinIva), ClientesResumenMensualConsulta::formatearMoneda($iva), ClientesResumenMensualConsulta::formatearMoneda($conIva), ClientesResumenMensualConsulta::formatearMoneda($pagado)];

        $this->celda($x, $y, $w[0] + $w[1] + $w[2] + $w[3], $h, $etiqueta, 'R', $fillBase, true);
        $x += $w[0] + $w[1] + $w[2] + $w[3];
        foreach ([4, 5, 6] as $i => $col) {
            $this->celda($x, $y, $w[$col], $h, $fmt[$i], 'R', $fillIva, true);
            $x += $w[$col];
        }
        $this->celda($x, $y, $w[7], $h, $fmt[3], 'R', $fillBase, true);
        $x += $w[7];
        $this->celda($x, $y, $w[8], $h, '', 'L', $fillBase, true);
        $x += $w[8];
        $this->celda($x, $y, $w[9], $h, '', 'L', $fillBase, true);
        $this->SetY($y + $h);
    }

    private function dibujarFilaDatos(object $fila): void
    {
        /** @var list<object> $dets */
        $dets = is_array($fila->determinaciones ?? null) ? $fila->determinaciones : [];
        $lineasDet = $this->lineasDeterminaciones($dets);
        $alto = max(self::ALTO_FILA_MIN, (count($lineasDet) * self::ALTO_LINEA_DET) + 1.6);
        $this->asegurarEspacio($alto + 1);

        $w = $this->anchos();
        $y = $this->GetY();
        $x = self::MARGEN;
        $fecha = $fila->fechhoy !== '' ? Carbon::parse($fila->fechhoy)->format('d/m/Y') : '';
        $fillIva = [212, 235, 247];
        $fillBase = [255, 255, 255];
        $fillDet = [248, 250, 252];

        $valores = [
            [$fecha, 'L', $fillBase],
            [(string) ($fila->cliente ?? ''), 'L', $fillBase],
            [(string) ($fila->nombreProtocolo ?? ''), 'L', $fillBase],
            [(string) ($fila->nombre ?? ''), 'L', $fillBase],
            [ClientesResumenMensualConsulta::formatearMoneda((float) $fila->sin_iva), 'R', $fillIva],
            [ClientesResumenMensualConsulta::formatearMoneda((float) $fila->iva), 'R', $fillIva],
            [ClientesResumenMensualConsulta::formatearMoneda((float) $fila->con_iva), 'R', $fillIva],
            [ClientesResumenMensualConsulta::formatearMoneda((float) $fila->pagado), 'R', $fillBase],
            [(string) ($fila->mediodepago ?? ''), 'L', $fillBase],
        ];
        foreach ($valores as $i => [$txt, $align, $fill]) {
            $this->celda($x, $y, $w[$i], $alto, $txt, $align, $fill, false);
            $x += $w[$i];
        }
        $this->celda($x, $y, $w[9], $alto, implode("\n", $lineasDet), 'L', $fillDet, false, 6);
        $this->SetY($y + $alto);
    }

    /**
     * @param  list<object>  $dets
     * @return list<string>
     */
    private function lineasDeterminaciones(array $dets): array
    {
        if ($dets === []) {
            return ['Sin determinaciones registradas.'];
        }

        $lineas = [];
        foreach ($dets as $det) {
            $lineas[] = trim((string) ($det->nombre ?? ''))
                .'  '
                .ClientesResumenMensualConsulta::formatearMoneda((float) ($det->precio ?? 0));
        }

        return $lineas;
    }

    /**
     * @param  array<string, mixed>  $info
     * @param  array<string, mixed>  $totales
     */
    private function dibujarDescuento(array $info, array $totales): void
    {
        $this->Ln(4);
        $this->asegurarEspacio(16);
        $util = $this->getPageWidth() - (2 * self::MARGEN);
        $y = $this->GetY();
        $wTitulo = $util * 0.18;
        $wItem = ($util - $wTitulo) / 3;
        $h = 12.0;
        $x = self::MARGEN;

        $this->SetFillColor(22, 163, 74);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(21, 128, 61);
        TcpdfFuenteArial::aplicar($this, 'B', 9);
        $this->Rect($x, $y, $wTitulo, $h, 'DF');
        $this->SetXY($x + 1, $y + 3.5);
        $this->Cell($wTitulo - 2, 5, (string) ($info['etiqueta'] ?? ''), 0, 0, 'C');
        $x += $wTitulo;

        $items = [
            ['Neto sin IVA', ClientesResumenMensualConsulta::formatearMoneda((float) ($totales['sum_cd_sin_iva'] ?? 0))],
            ['IVA', ClientesResumenMensualConsulta::formatearMoneda((float) ($totales['sum_cd_iva'] ?? 0))],
            ['Precio con IVA', ClientesResumenMensualConsulta::formatearMoneda((float) ($totales['sum_cd_con_iva'] ?? 0))],
        ];
        $this->SetFillColor(21, 128, 61);
        foreach ($items as $item) {
            $this->Rect($x, $y, $wItem, $h, 'DF');
            TcpdfFuenteArial::aplicar($this, '', 6);
            $this->SetXY($x, $y + 1.5);
            $this->Cell($wItem, 3.5, $item[0], 0, 0, 'C');
            TcpdfFuenteArial::aplicar($this, 'B', 10);
            $this->SetXY($x, $y + 5.5);
            $this->Cell($wItem, 5, $item[1], 0, 0, 'C');
            $x += $wItem;
        }
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(197, 205, 216);
        $this->SetY($y + $h);
    }

    private function asegurarEspacio(float $alto): void
    {
        $limite = $this->getPageHeight() - 8.0;
        if ($this->GetY() + $alto <= $limite) {
            return;
        }
        $this->AddPage();
        $this->dibujarEncabezados();
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $fill
     */
    private function celda(
        float $x,
        float $y,
        float $w,
        float $h,
        string $txt,
        string $align,
        array $fill,
        bool $bold = false,
        float $size = 6.5,
    ): void {
        $this->SetFillColor($fill[0], $fill[1], $fill[2]);
        $this->Rect($x, $y, $w, $h, 'DF');
        TcpdfFuenteArial::aplicar($this, $bold ? 'B' : '', $size);
        $this->SetTextColor(51, 51, 51);
        $this->SetXY($x + 0.5, $y + 0.4);
        $this->MultiCell(
            $w - 1.0,
            self::ALTO_LINEA_DET,
            $txt,
            0,
            $align,
            false,
            0,
            $x + 0.5,
            $y + 0.4,
            true,
            0,
            false,
            true,
            $h - 0.6,
            'T',
            false,
        );
    }
}
