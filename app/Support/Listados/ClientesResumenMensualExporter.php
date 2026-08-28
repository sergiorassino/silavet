<?php

namespace App\Support\Listados;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ClientesResumenMensualExporter
{
    private const FMT_NUM = '#,##0.00';

    /** @var list<string> */
    public const ENCABEZADOS = [
        'Fecha',
        'Cliente',
        'Protocolo',
        'Paciente',
        'Neto sin IVA',
        'IVA',
        'Precio con IVA',
        'Pagado',
        'Medio pago',
        'Determinaciones',
    ];

    /**
     * @param  iterable<object>  $filas
     * @param  array{idClientes?: int|null, fechaDesde?: string, fechaHasta?: string}  $filtros
     * @return array{spreadsheet: Spreadsheet, filename: string}
     */
    public function buildXlsx(iterable $filas, array $filtros = []): array
    {
        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Pacientes');

        $filtros = ClientesResumenMensualConsulta::normalizarFiltros($filtros);
        $info = ClientesResumenMensualConsulta::infoClienteFiltro($filtros);
        $periodo = ClientesResumenMensualConsulta::etiquetaPeriodo($filtros['fechaDesde'], $filtros['fechaHasta']);

        $hoja->mergeCells('A1:J1');
        $hoja->setCellValue('A1', 'Clientes resumen mensual');
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $hoja->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $hoja->mergeCells('A2:J2');
        $hoja->setCellValue('A2', 'Periodo: '.$periodo.'  |  Cliente: '.$info['nombre']);
        $hoja->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $fila = 4;
        foreach (self::ENCABEZADOS as $i => $titulo) {
            $hoja->setCellValue([$i + 1, $fila], $titulo);
        }
        $hoja->getStyle('A4:J4')->getFont()->setBold(true);
        $this->relleno('A4:D4', $hoja, 'E8EDF3');
        $this->relleno('E4:G4', $hoja, 'B8DFF5');
        $this->relleno('H4:J4', $hoja, 'E8EDF3');
        $hoja->getStyle('E4:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $lista = is_array($filas) ? $filas : iterator_to_array($filas);
        if ($lista === []) {
            $fila++;
            $hoja->mergeCells('A'.$fila.':J'.$fila);
            $hoja->setCellValue('A'.$fila, ClientesResumenMensualConsulta::mensajeVacio($filtros, $info));
            $hoja->getStyle('A'.$fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        } else {
            $fila = $this->escribirBloques($hoja, $lista, $fila, $info);
        }

        $hoja->getColumnDimension('A')->setWidth(12);
        $hoja->getColumnDimension('B')->setWidth(22);
        $hoja->getColumnDimension('C')->setWidth(12);
        $hoja->getColumnDimension('D')->setWidth(20);
        $hoja->getColumnDimension('E')->setWidth(14);
        $hoja->getColumnDimension('F')->setWidth(12);
        $hoja->getColumnDimension('G')->setWidth(16);
        $hoja->getColumnDimension('H')->setWidth(12);
        $hoja->getColumnDimension('I')->setWidth(14);
        $hoja->getColumnDimension('J')->setWidth(45);

        return [
            'spreadsheet' => $spreadsheet,
            'filename' => 'clientes-resumen-mensual_'.$filtros['fechaDesde'].'_'.$filtros['fechaHasta'].'.xlsx',
        ];
    }

    public function escribirEnSalida(Spreadsheet $spreadsheet): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        (new Xlsx($spreadsheet))->save('php://output');
    }

    /**
     * @param  list<object>  $filas
     * @param  array{mostrar: bool, nombre: string, pct: float, etiqueta: string}  $info
     */
    private function escribirBloques(Worksheet $hoja, array $filas, int $fila, array $info): int
    {
        $bloques = ClientesResumenMensualConsulta::bloquesAgrupados($filas);
        $totales = ClientesResumenMensualConsulta::acumular($filas);

        foreach ($bloques as $bloque) {
            if ($bloque['tipo'] === 'grupo') {
                $fila++;
                $hoja->mergeCells('A'.$fila.':J'.$fila);
                $hoja->setCellValue('A'.$fila, (string) $bloque['cliente']);
                $hoja->getStyle('A'.$fila)->getFont()->setBold(true);
                $this->relleno('A'.$fila.':J'.$fila, $hoja, 'DCE3EC');
                continue;
            }
            if ($bloque['tipo'] === 'subtotal') {
                $fila++;
                $this->escribirTotalesFila(
                    $hoja,
                    $fila,
                    'Subtotal '.$bloque['cliente'].' ('.$bloque['cantidad'].' pac.)',
                    (float) $bloque['sum_sin_iva'],
                    (float) $bloque['sum_iva'],
                    (float) $bloque['sum_con_iva'],
                    (float) $bloque['sum_pagado'],
                    'F3F4F6',
                    'A8D8F0',
                );
                continue;
            }

            $registro = $bloque['fila'];
            $fila++;
            $fecha = $registro->fechhoy !== ''
                ? Carbon::parse($registro->fechhoy)->format('d/m/Y')
                : '';
            $hoja->setCellValue('A'.$fila, $fecha);
            $hoja->setCellValue('B'.$fila, (string) ($registro->cliente ?? ''));
            $hoja->setCellValue('C'.$fila, (string) ($registro->nombreProtocolo ?? ''));
            $hoja->setCellValue('D'.$fila, (string) ($registro->nombre ?? ''));
            $hoja->setCellValue('E'.$fila, (float) $registro->sin_iva);
            $hoja->setCellValue('F'.$fila, (float) $registro->iva);
            $hoja->setCellValue('G'.$fila, (float) $registro->con_iva);
            $hoja->setCellValue('H'.$fila, (float) $registro->pagado);
            $hoja->setCellValue('I'.$fila, (string) ($registro->mediodepago ?? ''));
            $hoja->setCellValue('J'.$fila, $this->textoDeterminaciones($registro));
            $hoja->getStyle('E'.$fila.':H'.$fila)->getNumberFormat()->setFormatCode(self::FMT_NUM);
            $this->relleno('E'.$fila.':G'.$fila, $hoja, 'D4EBF7');
            $hoja->getStyle('J'.$fila)->getAlignment()->setWrapText(true);
        }

        $fila++;
        $this->escribirTotalesFila(
            $hoja,
            $fila,
            'Total general ('.$totales['cantidad'].' pac.)',
            $totales['sum_sin_iva'],
            $totales['sum_iva'],
            $totales['sum_con_iva'],
            $totales['sum_pagado'],
            'EEF2FF',
            'B8DFF5',
        );

        if (! empty($info['mostrar'])) {
            $fila += 2;
            $hoja->mergeCells('A'.$fila.':B'.$fila);
            $hoja->setCellValue('A'.$fila, (string) $info['etiqueta']);
            $hoja->setCellValue('C'.$fila, 'Neto sin IVA');
            $hoja->setCellValue('D'.$fila, $totales['sum_cd_sin_iva']);
            $hoja->setCellValue('E'.$fila, 'IVA');
            $hoja->setCellValue('F'.$fila, $totales['sum_cd_iva']);
            $hoja->setCellValue('G'.$fila, 'Precio con IVA');
            $hoja->setCellValue('H'.$fila, $totales['sum_cd_con_iva']);
            $hoja->getStyle('A'.$fila.':H'.$fila)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $this->relleno('A'.$fila.':B'.$fila, $hoja, '16A34A');
            $this->relleno('C'.$fila.':H'.$fila, $hoja, '15803D');
            $hoja->getStyle('D'.$fila)->getNumberFormat()->setFormatCode(self::FMT_NUM);
            $hoja->getStyle('F'.$fila)->getNumberFormat()->setFormatCode(self::FMT_NUM);
            $hoja->getStyle('H'.$fila)->getNumberFormat()->setFormatCode(self::FMT_NUM);
            $hoja->getStyle('C'.$fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $hoja->getStyle('E'.$fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $hoja->getStyle('G'.$fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        return $fila;
    }

    private function escribirTotalesFila(
        Worksheet $hoja,
        int $fila,
        string $etiqueta,
        float $sinIva,
        float $iva,
        float $conIva,
        float $pagado,
        string $colorBase,
        string $colorIva,
    ): void {
        $hoja->mergeCells('A'.$fila.':D'.$fila);
        $hoja->setCellValue('A'.$fila, $etiqueta);
        $hoja->setCellValue('E'.$fila, $sinIva);
        $hoja->setCellValue('F'.$fila, $iva);
        $hoja->setCellValue('G'.$fila, $conIva);
        $hoja->setCellValue('H'.$fila, $pagado);
        $hoja->getStyle('A'.$fila.':J'.$fila)->getFont()->setBold(true);
        $this->relleno('A'.$fila.':D'.$fila, $hoja, $colorBase);
        $this->relleno('E'.$fila.':G'.$fila, $hoja, $colorIva);
        $this->relleno('H'.$fila.':J'.$fila, $hoja, $colorBase);
        $hoja->getStyle('E'.$fila.':H'.$fila)->getNumberFormat()->setFormatCode(self::FMT_NUM);
        $hoja->getStyle('A'.$fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function textoDeterminaciones(object $registro): string
    {
        /** @var list<object> $dets */
        $dets = is_array($registro->determinaciones ?? null) ? $registro->determinaciones : [];
        if ($dets === []) {
            return 'Sin determinaciones registradas.';
        }

        $partes = [];
        foreach ($dets as $det) {
            $partes[] = trim((string) ($det->nombre ?? ''));
        }

        return implode("\n", $partes);
    }

    private function relleno(string $rango, Worksheet $hoja, string $rgb): void
    {
        $hoja->getStyle($rango)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($rgb);
    }
}
