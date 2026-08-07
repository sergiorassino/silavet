<?php

namespace App\Support\Tesoreria;

use App\Models\Movimiento;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Excel del listado de movimientos de caja (variante tesoreria_pacientes / labvetciudad).
 */
final class MovimientosCajaExporter
{
    /** @var list<string> */
    public const ENCABEZADOS = [
        'Fechhora',
        'Cuenta',
        'Ingreso / Egreso',
        'Cliente',
        'Paciente',
        'Concepto',
        'Proveedores',
        'Comprobante',
        'Monto',
        'Obs',
    ];

    /**
     * @param  iterable<Movimiento>  $filas
     * @return array{spreadsheet: Spreadsheet, filename: string}
     */
    public function buildXlsx(
        iterable $filas,
        string $busqueda = '',
        string $desde = '',
        string $hasta = '',
    ): array {
        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Movimientos');

        $this->escribirEncabezados($hoja, self::ENCABEZADOS);

        $fila = 2;
        foreach ($filas as $mov) {
            $cliente = ((int) ($mov->idClientes ?? 0) > 0)
                ? (string) ($mov->cliente?->nombre ?? '')
                : '';
            $proveedor = ((int) ($mov->idProveedores ?? 0) > 0)
                ? (string) ($mov->proveedor?->proveedor ?? '')
                : '';

            $this->escribirFila($hoja, $fila, [
                $mov->fechhora?->format('d/m/Y H:i:s') ?? '',
                (string) ($mov->cuenta?->nombreMedioPago ?? ''),
                (string) ($mov->tipoMovimiento?->tipoMovimiento ?? ''),
                $cliente,
                $mov->etiquetaPaciente(),
                (string) ($mov->concepto?->concepto ?? ''),
                $proveedor,
                (string) ($mov->comprobante ?? ''),
                round((float) ($mov->monto ?? 0), 2),
                (string) ($mov->obs ?? ''),
            ]);
            $fila++;
        }

        $this->estilizarEncabezado($hoja, count(self::ENCABEZADOS));

        return [
            'spreadsheet' => $spreadsheet,
            'filename' => $this->nombreArchivo($busqueda, $desde, $hasta),
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

    private function nombreArchivo(string $busqueda, string $desde, string $hasta): string
    {
        $slug = $busqueda !== '' ? '-'.preg_replace('/[^a-z0-9]+/i', '-', $busqueda) : '';
        $periodo = ($desde !== '' || $hasta !== '')
            ? '-'.($desde !== '' ? $desde : 'inicio').'_'.($hasta !== '' ? $hasta : 'hoy')
            : '-'.now()->format('Y-m-d');

        return 'movimientos-caja'.($slug ?: '').$periodo.'.xlsx';
    }
}
