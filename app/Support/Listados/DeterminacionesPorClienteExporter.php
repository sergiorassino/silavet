<?php

namespace App\Support\Listados;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class DeterminacionesPorClienteExporter
{
    /** Encabezados por grupo, iguales al Excel legacy de ScriptCase. */
    /** @var list<string> */
    public const ENCABEZADOS = [
        'Nombre',
        'Nombre',
        'Fecha',
        'Nombre Protocolo',
        'Nombre',
        'Precio',
    ];

    /**
     * @param  iterable<object>  $filas
     * @param  array<string, mixed>  $filtros
     * @return array{spreadsheet: Spreadsheet, filename: string}
     */
    public function buildXlsx(iterable $filas, array $filtros = []): array
    {
        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Determinaciones');

        $this->aplicarAnchos($hoja);

        $bloques = DeterminacionesPorClienteConsulta::bloquesAgrupados($filas);
        $fila = 1;
        $totalRegistros = 0;
        $totalPrecio = 0.0;

        foreach ($bloques as $bloque) {
            $cliente = trim((string) ($bloque['cliente'] ?? '')) !== ''
                ? (string) $bloque['cliente']
                : 'Sin cliente';
            $cantidad = (int) ($bloque['cantidad'] ?? 0);
            $suma = round((float) ($bloque['sumaPrecio'] ?? 0), 2);

            $this->escribirCelda($hoja, 1, $fila, 'Cliente '.$cliente);
            $this->estilizarFilaGrupo($hoja, $fila);
            $fila++;

            $this->escribirCelda($hoja, 1, $fila, 'Cantidad '.$cantidad);
            $this->estilizarFilaGrupo($hoja, $fila);
            $fila++;

            $this->escribirEncabezados($hoja, $fila);
            $fila++;

            foreach ($bloque['filas'] as $registro) {
                $precio = round((float) ($registro->precio ?? 0), 2);
                $fecha = $registro->fechhoy !== ''
                    ? Carbon::parse($registro->fechhoy)->format('d/m/Y')
                    : '';

                $this->escribirFilaDatos($hoja, $fila, [
                    (string) ($registro->cliente ?? ''),
                    (string) ($registro->determinacion ?? ''),
                    $fecha,
                    (string) ($registro->protocolo ?? ''),
                    (string) ($registro->paciente ?? ''),
                    $precio,
                ]);
                $fila++;
                $totalRegistros++;
                $totalPrecio += $precio;
            }

            $this->escribirCelda($hoja, 1, $fila, ' - Suma');
            $this->escribirCelda($hoja, 6, $fila, $suma);
            $hoja->getStyle([6, $fila])->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $this->estilizarFilaSuma($hoja, $fila);
            $fila++;

            $fila++;
        }

        $this->escribirCelda($hoja, 1, $fila, 'Total Acumulado('.$totalRegistros.') - Suma');
        $this->escribirCelda($hoja, 6, $fila, round($totalPrecio, 2));
        $hoja->getStyle([6, $fila])->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $this->estilizarFilaTotal($hoja, $fila);

        return [
            'spreadsheet' => $spreadsheet,
            'filename' => $this->nombreArchivo($filtros),
        ];
    }

    public function escribirEnSalida(Spreadsheet $spreadsheet): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        (new Xlsx($spreadsheet))->save('php://output');
    }

    private function escribirEncabezados(Worksheet $hoja, int $fila): void
    {
        $col = 1;
        foreach (self::ENCABEZADOS as $encabezado) {
            $hoja->setCellValue([$col, $fila], $encabezado);
            $col++;
        }

        $hoja->getStyle([1, $fila, 6, $fila])->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '1E3A5F'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'C5D9ED'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '4A7CB0'],
                ],
            ],
        ]);
    }

    /**
     * @param  list<string|float|int>  $valores
     */
    private function escribirFilaDatos(Worksheet $hoja, int $fila, array $valores): void
    {
        $col = 1;
        foreach ($valores as $valor) {
            $hoja->setCellValue([$col, $fila], $valor);
            $col++;
        }
        $hoja->getStyle([6, $fila])->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $hoja->getStyle([3, $fila])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $hoja->getStyle([6, $fila])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function escribirCelda(Worksheet $hoja, int $col, int $fila, string|int|float $valor): void
    {
        $hoja->setCellValue([$col, $fila], $valor);
    }

    private function estilizarFilaGrupo(Worksheet $hoja, int $fila): void
    {
        $hoja->getStyle([1, $fila, 6, $fila])->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8EEF4'],
            ],
        ]);
    }

    private function estilizarFilaSuma(Worksheet $hoja, int $fila): void
    {
        $hoja->getStyle([1, $fila, 6, $fila])->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
        ]);
        $hoja->getStyle([6, $fila])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function estilizarFilaTotal(Worksheet $hoja, int $fila): void
    {
        $hoja->getStyle([1, $fila, 6, $fila])->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A5F'],
            ],
        ]);
        $hoja->getStyle([6, $fila])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function aplicarAnchos(Worksheet $hoja): void
    {
        $hoja->getColumnDimensionByColumn(1)->setWidth(28);
        $hoja->getColumnDimensionByColumn(2)->setWidth(42);
        $hoja->getColumnDimensionByColumn(3)->setWidth(14);
        $hoja->getColumnDimensionByColumn(4)->setWidth(18);
        $hoja->getColumnDimensionByColumn(5)->setWidth(22);
        $hoja->getColumnDimensionByColumn(6)->setWidth(16);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function nombreArchivo(array $filtros): string
    {
        $desde = trim((string) ($filtros['fechaDesde'] ?? ''));
        $hasta = trim((string) ($filtros['fechaHasta'] ?? ''));
        $periodo = ($desde === '' && $hasta === '')
            ? 'historial'
            : ($desde !== '' ? $desde : 'inicio').'_'.($hasta !== '' ? $hasta : 'hoy');

        return 'determinaciones-por-cliente-'.$periodo.'-'.now()->format('Y-m-d').'.xlsx';
    }
}
