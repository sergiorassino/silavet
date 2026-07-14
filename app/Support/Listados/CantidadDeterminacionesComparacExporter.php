<?php

namespace App\Support\Listados;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class CantidadDeterminacionesComparacExporter
{
    /** @var list<string> */
    public const ENCABEZADOS = [
        'Determinación',
        'Período 1',
        'Período 2',
        'Diferencia (P2 − P1)',
    ];

    /**
     * @param  iterable<object>  $filas
     * @param  array<string, mixed>  $filtros
     * @param  array<string, mixed>  $resumen
     * @return array{spreadsheet: Spreadsheet, filename: string}
     */
    public function buildXlsx(iterable $filas, array $filtros = [], array $resumen = []): array
    {
        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Comparativa');

        $hoja->setCellValue([1, 1], 'Cantidad de determinaciones (comparativa)');
        $hoja->setCellValue([1, 2], 'Período 1: '.((string) ($resumen['periodo1'] ?? '—')));
        $hoja->setCellValue([1, 3], 'Período 2: '.((string) ($resumen['periodo2'] ?? '—')));
        $hoja->getStyle([1, 1, 1, 1])->getFont()->setBold(true);

        $this->escribirEncabezados($hoja, self::ENCABEZADOS, 5);

        $fila = 6;
        foreach ($filas as $registro) {
            $this->escribirFila($hoja, $fila, [
                (string) ($registro->nombre ?? ''),
                (int) ($registro->cantidad1 ?? 0),
                (int) ($registro->cantidad2 ?? 0),
                (int) ($registro->diferencia ?? 0),
            ]);
            $fila++;
        }

        $this->escribirFila($hoja, $fila, [
            'Totales',
            (int) ($resumen['total1'] ?? 0),
            (int) ($resumen['total2'] ?? 0),
            (int) ($resumen['totalDiferencia'] ?? 0),
        ]);
        $hoja->getStyle([1, $fila, 4, $fila])->getFont()->setBold(true);

        $this->estilizarEncabezado($hoja, count(self::ENCABEZADOS), 5);

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

    /**
     * @param  list<string>  $encabezados
     */
    private function escribirEncabezados(Worksheet $hoja, array $encabezados, int $fila): void
    {
        $col = 1;
        foreach ($encabezados as $encabezado) {
            $hoja->setCellValue([$col, $fila], $encabezado);
            $col++;
        }
    }

    /**
     * @param  list<string|int>  $valores
     */
    private function escribirFila(Worksheet $hoja, int $fila, array $valores): void
    {
        $col = 1;
        foreach ($valores as $valor) {
            $hoja->setCellValue([$col, $fila], $valor);
            $col++;
        }
    }

    private function estilizarEncabezado(Worksheet $hoja, int $columnas, int $filaEncabezado): void
    {
        $hoja->getStyle([1, $filaEncabezado, $columnas, $filaEncabezado])->getFont()->setBold(true);
        for ($col = 1; $col <= $columnas; $col++) {
            $hoja->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function nombreArchivo(array $filtros): string
    {
        $p1 = trim((string) ($filtros['periodo1Desde'] ?? '')).'_'.trim((string) ($filtros['periodo1Hasta'] ?? ''));
        $p2 = trim((string) ($filtros['periodo2Desde'] ?? '')).'_'.trim((string) ($filtros['periodo2Hasta'] ?? ''));

        return 'cantidad-determinaciones-comparac-'.$p1.'-vs-'.$p2.'-'.now()->format('Y-m-d').'.xlsx';
    }
}
