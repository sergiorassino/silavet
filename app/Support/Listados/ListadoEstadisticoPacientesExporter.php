<?php

namespace App\Support\Listados;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ListadoEstadisticoPacientesExporter
{
    /** @var list<string> */
    public const ENCABEZADOS = [
        '#',
        'Clientes',
        'Especies',
        'Razas',
        'Fecha',
        'Protocolo',
        'Nombre',
        'Propietario',
        'Estado',
        'Precio',
        'Pagado',
    ];

    /**
     * @param  iterable<object>  $filas
     * @param  array{
     *     idClientes?: int|null,
     *     paciente?: string,
     *     idEspecies?: int|null,
     *     idRazas?: int|null,
     *     fechaDesde?: string,
     *     fechaHasta?: string,
     *     agruparPorCliente?: bool
     * }  $filtros
     * @return array{spreadsheet: Spreadsheet, filename: string}
     */
    public function buildXlsx(iterable $filas, array $filtros = []): array
    {
        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Pacientes');

        $this->escribirEncabezados($hoja, self::ENCABEZADOS);

        $agrupar = (bool) ($filtros['agruparPorCliente'] ?? false);
        $fila = 2;
        $numero = 1;
        $totalPrecio = 0.0;
        $totalPagado = 0.0;

        if ($agrupar) {
            $bloques = ListadoEstadisticoPacientesConsulta::bloquesAgrupados($filas);
            foreach ($bloques as $bloque) {
                if ($bloque['tipo'] === 'grupo') {
                    $this->escribirFila($hoja, $fila, [
                        '',
                        (string) ($bloque['cliente'] ?? ''),
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        'Subtotal ('.(int) ($bloque['cantidad'] ?? 0).')',
                        round((float) ($bloque['subtotal_precio'] ?? 0), 2),
                        round((float) ($bloque['subtotal_pagado'] ?? 0), 2),
                    ]);
                    $hoja->getStyle([1, $fila, count(self::ENCABEZADOS), $fila])->getFont()->setBold(true);
                    $fila++;
                    continue;
                }

                $registro = $bloque['fila'];
                $precio = round((float) ($registro->precio ?? 0), 2);
                $pagado = round((float) ($registro->pagado ?? 0), 2);
                $totalPrecio += $precio;
                $totalPagado += $pagado;

                $this->escribirFila($hoja, $fila, $this->valoresFila($numero, $registro, $precio, $pagado));
                $fila++;
                $numero++;
            }
        } else {
            foreach ($filas as $registro) {
                $precio = round((float) ($registro->precio ?? 0), 2);
                $pagado = round((float) ($registro->pagado ?? 0), 2);
                $totalPrecio += $precio;
                $totalPagado += $pagado;

                $this->escribirFila($hoja, $fila, $this->valoresFila($numero, $registro, $precio, $pagado));
                $fila++;
                $numero++;
            }
        }

        $this->escribirFila($hoja, $fila, [
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'Total:',
            round($totalPrecio, 2),
            round($totalPagado, 2),
        ]);
        $hoja->getStyle([1, $fila, count(self::ENCABEZADOS), $fila])->getFont()->setBold(true);
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
     * @return list<string|float|int>
     */
    private function valoresFila(int $numero, object $registro, float $precio, float $pagado): array
    {
        $fecha = $registro->fechhoy !== ''
            ? \Carbon\Carbon::parse($registro->fechhoy)->format('d/m/Y')
            : '';

        return [
            $numero,
            (string) ($registro->cliente ?? ''),
            (string) ($registro->especie ?? ''),
            (string) ($registro->raza ?? ''),
            $fecha,
            (string) ($registro->nombreProtocolo ?? ''),
            (string) ($registro->nombre ?? ''),
            (string) ($registro->propietario ?? ''),
            (string) ($registro->estado ?? ''),
            $precio,
            $pagado,
        ];
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

        return 'listado-estadistico-pacientes-'.$periodo.'-'.now()->format('Y-m-d').'.xlsx';
    }
}
