<?php

namespace App\Support\Listados;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class HistorialDeterminacionesExporter
{
    /** @var list<string> */
    public const ENCABEZADOS = [
        'Fecha',
        'Cliente',
        'Protocolo',
        'Paciente',
        'Especie',
        'Grupo',
        'Determinación',
        'Valor',
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
        $hoja->setTitle('Historial');

        $this->escribirEncabezados($hoja, self::ENCABEZADOS);

        $fila = 2;
        foreach ($filas as $registro) {
            $fecha = $registro->fechhoy !== ''
                ? Carbon::parse($registro->fechhoy)->format('d/m/Y')
                : '';

            $this->escribirFila($hoja, $fila, [
                $fecha,
                (string) ($registro->cliente ?? ''),
                (string) ($registro->protocolo ?? ''),
                (string) ($registro->paciente ?? ''),
                (string) ($registro->especie ?? ''),
                (string) ($registro->grupo ?? ''),
                (string) ($registro->determinacion ?? ''),
                (string) ($registro->valor ?? ''),
            ]);
            $fila++;
        }

        $this->estilizarEncabezado($hoja, count(self::ENCABEZADOS));

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
    private function escribirEncabezados(Worksheet $hoja, array $encabezados): void
    {
        $col = 1;
        foreach ($encabezados as $encabezado) {
            $hoja->setCellValue([$col, 1], $encabezado);
            $col++;
        }
    }

    /**
     * @param  list<string>  $valores
     */
    private function escribirFila(Worksheet $hoja, int $fila, array $valores): void
    {
        $col = 1;
        foreach ($valores as $valor) {
            $hoja->setCellValue([$col, $fila], $valor);
            $col++;
        }
    }

    private function estilizarEncabezado(Worksheet $hoja, int $columnas): void
    {
        $hoja->getStyle([1, 1, $columnas, 1])->getFont()->setBold(true);
        for ($col = 1; $col <= $columnas; $col++) {
            $hoja->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
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

        return 'historial-determinaciones-'.$periodo.'-'.now()->format('Y-m-d').'.xlsx';
    }
}
