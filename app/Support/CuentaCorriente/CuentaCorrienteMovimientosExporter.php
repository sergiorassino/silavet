<?php

namespace App\Support\CuentaCorriente;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class CuentaCorrienteMovimientosExporter
{
    /** @var list<string> */
    public const ENCABEZADOS_CLIENTES = [
        'Cliente',
        'Dirección',
        'Teléfono',
        'Saldo',
    ];

    /** @var list<string> */
    public const ENCABEZADOS_DETALLE = [
        '#',
        'Nombre',
        'Id Cuentas',
        'Fecha/Hora',
        'Monto',
        'Obs',
    ];

    /**
     * @param  iterable<object>  $filas
     * @return array{spreadsheet: Spreadsheet, filename: string}
     */
    public function buildXlsxClientes(iterable $filas, string $busqueda = ''): array
    {
        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Clientes');

        $this->escribirEncabezados($hoja, self::ENCABEZADOS_CLIENTES);

        $fila = 2;
        $saldoTotal = 0.0;
        foreach ($filas as $cliente) {
            $saldo = round((float) ($cliente->saldo_total ?? 0), 2);
            $saldoTotal += $saldo;
            $telefono = trim((string) ($cliente->telefono1 ?? ''));
            if ($telefono === '' && ! empty($cliente->telefono2)) {
                $telefono = trim((string) $cliente->telefono2);
            }

            $this->escribirFila($hoja, $fila, [
                (string) ($cliente->nombre ?? ''),
                (string) ($cliente->direccion ?? ''),
                $telefono,
                $saldo,
            ]);
            $fila++;
        }

        $this->escribirFila($hoja, $fila, ['', '', 'Total', round($saldoTotal, 2)]);
        $this->estilizarEncabezado($hoja, count(self::ENCABEZADOS_CLIENTES));

        return [
            'spreadsheet' => $spreadsheet,
            'filename' => $this->nombreArchivoClientes($busqueda),
        ];
    }

    /**
     * @param  iterable<object>  $filas
     * @return array{spreadsheet: Spreadsheet, filename: string}
     */
    public function buildXlsxDetalle(
        iterable $filas,
        string $clienteNombre,
        string $desde,
        string $hasta,
        ?float $saldoAnterior = null,
    ): array {
        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Detalle');

        $this->escribirEncabezados($hoja, self::ENCABEZADOS_DETALLE);

        $fila = 2;
        $numero = 1;
        $totalMonto = 0.0;
        foreach ($filas as $mov) {
            $monto = round((float) ($mov->monto ?? 0), 2);
            $totalMonto += $monto;

            $fechhora = $mov->fechhora !== ''
                ? Carbon::parse($mov->fechhora)->format('d/m/Y H:i:s')
                : '';

            $this->escribirFila($hoja, $fila, [
                $numero,
                (string) ($mov->etiquetaPaciente ?? ''),
                (string) ($mov->cuentaLabel ?? ''),
                $fechhora,
                $monto,
                (string) ($mov->obs ?? ''),
            ]);
            $fila++;
            $numero++;
        }

        if ($saldoAnterior !== null && trim($desde) !== '') {
            $this->escribirFila($hoja, $fila, [
                '',
                '',
                '',
                'Saldo anterior al '.Carbon::parse($desde)->format('d/m/Y'),
                round($saldoAnterior, 2),
                '',
            ]);
            $fila++;
        }

        $this->escribirFila($hoja, $fila, [
            '',
            '',
            '',
            'Total período:',
            round($totalMonto, 2),
            '',
        ]);
        $fila++;

        $this->escribirFila($hoja, $fila, [
            '',
            '',
            '',
            'TOTAL A LA FECHA:',
            CuentaCorrienteMovimientosConsulta::totalALaFecha($saldoAnterior, $totalMonto),
            '',
        ]);
        $this->estilizarEncabezado($hoja, count(self::ENCABEZADOS_DETALLE));

        return [
            'spreadsheet' => $spreadsheet,
            'filename' => $this->nombreArchivoDetalle($clienteNombre, $desde, $hasta),
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

    private function nombreArchivoClientes(string $busqueda): string
    {
        $slug = $busqueda !== '' ? '-'.preg_replace('/[^a-z0-9]+/i', '-', $busqueda) : '';

        return 'cuenta-corriente-clientes'.($slug ?: '').'-'.now()->format('Y-m-d').'.xlsx';
    }

    private function nombreArchivoDetalle(string $clienteNombre, string $desde, string $hasta): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $clienteNombre) ?: 'cliente';
        $periodo = ($desde === '' && $hasta === '')
            ? 'historial-completo'
            : ($desde !== '' ? $desde : 'inicio').'_'.($hasta !== '' ? $hasta : 'hoy');

        return 'cuenta-corriente-'.$slug.'-'.$periodo.'.xlsx';
    }
}
