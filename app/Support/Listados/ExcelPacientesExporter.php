<?php

namespace App\Support\Listados;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ExcelPacientesExporter
{
    /** @var list<string> */
    public const ENCABEZADOS = [
        'Cliente',
        'Especie',
        'Raza',
        'Sexo',
        'Edad',
        'Fecha',
        'Protocolo',
        'Nombre',
        'Propietario',
        'Estado',
        'Precio',
        'Observaciones',
        'Obs. interna',
        'Determinaciones',
    ];

    /**
     * @param  iterable<object>  $filas
     * @param  array{fechaDesde?: string, fechaHasta?: string}  $filtros
     * @return array{spreadsheet: Spreadsheet, filename: string}
     */
    public function buildXlsx(iterable $filas, array $filtros = []): array
    {
        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Pacientes');

        $totalColumnas = count(self::ENCABEZADOS);
        $this->escribirTitulo($hoja, $filtros, $totalColumnas);
        $this->escribirEncabezados($hoja, self::ENCABEZADOS);

        $fila = 3;
        foreach ($filas as $registro) {
            $fecha = $registro->fechhoy !== ''
                ? Carbon::parse($registro->fechhoy)->format('d/m/Y')
                : '';

            $this->escribirFila($hoja, $fila, [
                (string) ($registro->cliente ?? ''),
                (string) ($registro->especie ?? ''),
                (string) ($registro->raza ?? ''),
                (string) ($registro->sexo ?? ''),
                (string) ($registro->edad ?? ''),
                $fecha,
                (string) ($registro->nombreProtocolo ?? ''),
                (string) ($registro->nombre ?? ''),
                (string) ($registro->propietario ?? ''),
                (string) ($registro->estado ?? ''),
                round((float) ($registro->precio ?? 0), 2),
                (string) ($registro->observaciones ?? ''),
                (string) ($registro->obsInterna ?? ''),
                (string) ($registro->determinaciones ?? ''),
            ]);
            $fila++;
        }

        $this->estilizarEncabezado($hoja, $totalColumnas);
        $hoja->freezePane('A3');

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
     * @param  array{fechaDesde?: string, fechaHasta?: string}  $filtros
     */
    private function escribirTitulo(Worksheet $hoja, array $filtros, int $totalColumnas): void
    {
        $desde = trim((string) ($filtros['fechaDesde'] ?? ''));
        $hasta = trim((string) ($filtros['fechaHasta'] ?? ''));
        $textoDesde = $desde !== '' ? Carbon::parse($desde)->format('d/m/Y') : '—';
        $textoHasta = $hasta !== '' ? Carbon::parse($hasta)->format('d/m/Y') : '—';
        $titulo = 'Pacientes entre '.$textoDesde.' y '.$textoHasta;
        $generado = 'Generado: '.now()->format('d/m/Y H:i:s');

        $rich = new RichText;
        $runTitulo = $rich->createTextRun($titulo);
        $runTitulo->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
        $runGen = $rich->createTextRun("\n".$generado);
        $runGen->getFont()->setSize(8)->getColor()->setRGB('C5D9ED');

        $hoja->mergeCells([1, 1, $totalColumnas, 1]);
        $hoja->setCellValue([1, 1], $rich);
        $hoja->getRowDimension(1)->setRowHeight(42);
        $hoja->getStyle([1, 1, $totalColumnas, 1])->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A5F'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
    }

    /**
     * @param  list<string>  $encabezados
     */
    private function escribirEncabezados(Worksheet $hoja, array $encabezados): void
    {
        $col = 1;
        foreach ($encabezados as $encabezado) {
            $hoja->setCellValue([$col, 2], $encabezado);
            $col++;
        }
        $hoja->getRowDimension(2)->setRowHeight(22);
    }

    /**
     * @param  list<string|float|int>  $valores
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
        $hoja->getStyle([1, 2, $columnas, 2])->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4A7CB0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '1E3A5F'],
                ],
            ],
        ]);

        for ($col = 1; $col <= $columnas; $col++) {
            $hoja->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
    }

    /**
     * @param  array{fechaDesde?: string, fechaHasta?: string}  $filtros
     */
    private function nombreArchivo(array $filtros): string
    {
        $desde = trim((string) ($filtros['fechaDesde'] ?? ''));
        $hasta = trim((string) ($filtros['fechaHasta'] ?? ''));
        $periodo = ($desde === '' && $hasta === '')
            ? now()->format('Y-m-d')
            : ($desde !== '' ? $desde : 'inicio').'_'.($hasta !== '' ? $hasta : 'hoy');

        return 'Pacientes_'.$periodo.'.xlsx';
    }
}
