<?php

namespace App\Support\Protocolos;

use App\Livewire\Protocolos\PacienteIndex;
use App\Models\Paciente;
use App\Support\Precios\ListaPreciosConfig;
use App\Support\Resultados\ResultadosEstadosCatalog;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Excel del listado de pacientes (mismas columnas de datos que la grilla).
 */
final class PacienteListadoExporter
{
    /**
     * @param  iterable<Paciente>  $filas
     * @param  array{
     *     autogestion?: bool,
     *     mostrarListaPrecios?: bool,
     *     mostrarColumnaPagado?: bool,
     *     mostrarCadete?: bool,
     *     saldosAcumulados?: array<int, float>,
     *     vista?: string,
     *     fechaVista?: string,
     *     fechaDesde?: string,
     *     fechaHasta?: string,
     *     busqueda?: string
     * }  $opciones
     * @return array{spreadsheet: Spreadsheet, filename: string}
     */
    public function buildXlsx(iterable $filas, array $opciones = []): array
    {
        $autogestion = (bool) ($opciones['autogestion'] ?? false);
        $encabezados = $this->encabezados($opciones);
        $saldos = $opciones['saldosAcumulados'] ?? [];

        $spreadsheet = new Spreadsheet;
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Pacientes');

        $this->escribirEncabezados($hoja, $encabezados);

        $fila = 2;
        $numero = 1;
        foreach ($filas as $paciente) {
            $this->escribirFila(
                $hoja,
                $fila,
                $this->valoresFila($numero, $paciente, $opciones, $saldos, $autogestion)
            );
            $fila++;
            $numero++;
        }

        $this->estilizarEncabezado($hoja, count($encabezados));

        return [
            'spreadsheet' => $spreadsheet,
            'filename' => $this->nombreArchivo($opciones),
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
     * @param  array<string, mixed>  $opciones
     * @return list<string>
     */
    public function encabezados(array $opciones): array
    {
        if (! empty($opciones['autogestion'])) {
            return [
                '#',
                'Fecha',
                'Protocolo',
                'Nombre',
                'Tutor',
                'Especie',
                'Raza',
                'Sexo',
                'Edad',
                'Estado',
                'Precio Lista',
                'Desc.',
                'Precio c/desc',
                'Pagado',
                'Saldo',
            ];
        }

        $encabezados = [
            '#',
            'Cliente',
            'Fecha',
            'Protocolo',
            'Nombre',
            'Tutor',
        ];

        if (! empty($opciones['mostrarListaPrecios'])) {
            $encabezados[] = 'L/P';
        }

        $encabezados[] = 'Especie';
        $encabezados[] = 'Raza';
        $encabezados[] = 'Sexo';
        $encabezados[] = 'Edad';
        $encabezados[] = 'Precio';

        if (! empty($opciones['mostrarColumnaPagado'])) {
            $encabezados[] = 'Pagado';
        }
        if (! empty($opciones['mostrarCadete'])) {
            $encabezados[] = 'Cadete';
        }

        $encabezados[] = 'Estado';

        return $encabezados;
    }

    /**
     * @param  array<string, mixed>  $opciones
     * @param  array<int, float>  $saldos
     * @return list<string|float|int>
     */
    private function valoresFila(
        int $numero,
        Paciente $paciente,
        array $opciones,
        array $saldos,
        bool $autogestion,
    ): array {
        $esPago = $paciente->esPagoGlobal();
        $fecha = $paciente->fechhoy?->format('d/m/Y') ?? '';
        $protocolo = $esPago ? '' : (string) ($paciente->nombreProtocolo ?? '');
        $nombre = $esPago ? 'Pago global' : (string) ($paciente->nombre ?? '');
        $tutor = $esPago ? '' : (string) ($paciente->propietario ?? '');
        $especie = $esPago ? '' : (string) ($paciente->especie?->nombre ?? '');
        $raza = $esPago ? '' : (string) ($paciente->raza?->nombre ?? '');
        $sexo = $esPago ? '' : (string) ($paciente->sexo ?? '');
        $edad = $esPago ? '' : (string) ($paciente->edad ?? '');
        $estado = $esPago
            ? 'Pago'
            : (trim((string) ($paciente->estado ?? '')) !== ''
                ? (string) $paciente->estado
                : ResultadosEstadosCatalog::EN_PROC);

        if ($autogestion) {
            return [
                $numero,
                $fecha,
                $protocolo,
                $nombre,
                $tutor,
                $especie,
                $raza,
                $sexo,
                $edad,
                $estado,
                $esPago ? '' : round($paciente->precioLista(), 2),
                $esPago ? '' : round($paciente->descuentoImporte(), 2),
                $esPago ? '' : round($paciente->precioConDescuentoImporte(), 2),
                round((float) ($paciente->pagado ?? 0), 2),
                round((float) ($saldos[(int) $paciente->idPacientes] ?? 0), 2),
            ];
        }

        $precio = $esPago
            ? (! empty($opciones['mostrarColumnaPagado']) ? '' : round((float) ($paciente->pagado ?? 0), 2))
            : round((float) ($paciente->precio ?? 0), 2);

        $valores = [
            $numero,
            (string) ($paciente->cliente?->nombre ?? ''),
            $fecha,
            $protocolo,
            $nombre,
            $tutor,
        ];

        if (! empty($opciones['mostrarListaPrecios'])) {
            $valores[] = $esPago ? '' : ListaPreciosConfig::etiquetaParaPaciente($paciente);
        }

        $valores[] = $especie;
        $valores[] = $raza;
        $valores[] = $sexo;
        $valores[] = $edad;
        $valores[] = $precio;

        if (! empty($opciones['mostrarColumnaPagado'])) {
            $valores[] = round((float) ($paciente->pagado ?? 0), 2);
        }
        if (! empty($opciones['mostrarCadete'])) {
            $valores[] = $esPago ? '' : round((float) ($paciente->cadete ?? 0), 2);
        }

        $valores[] = $esPago ? '' : $estado;

        return $valores;
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
     * @param  array<string, mixed>  $opciones
     */
    private function nombreArchivo(array $opciones): string
    {
        $vista = (string) ($opciones['vista'] ?? PacienteIndex::VISTA_HOY);

        if ($vista === PacienteIndex::VISTA_HISTORIAL) {
            $desde = trim((string) ($opciones['fechaDesde'] ?? ''));
            $hasta = trim((string) ($opciones['fechaHasta'] ?? ''));
            $periodo = ($desde !== '' || $hasta !== '')
                ? '-'.($desde !== '' ? $desde : 'inicio').'_'.($hasta !== '' ? $hasta : 'hoy')
                : '-historial';
        } else {
            $fecha = trim((string) ($opciones['fechaVista'] ?? ''));
            $periodo = '-'.($fecha !== '' ? $fecha : now()->toDateString());
        }

        return 'pacientes'.$periodo.'-'.Carbon::now()->format('Y-m-d').'.xlsx';
    }
}
