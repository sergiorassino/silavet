<?php

namespace Tests\Unit;

use App\Livewire\Protocolos\PacienteIndex;
use App\Support\Protocolos\PacienteListadoConsulta;
use App\Support\Protocolos\PacienteListadoExporter;
use Tests\TestCase;

class PacienteListadoExcelTest extends TestCase
{
    public function test_fecha_ymd_acepta_iso_y_rechaza_otro_formato(): void
    {
        $this->assertSame('2024-06-01', PacienteListadoConsulta::fechaYmd('2024-06-01'));
        $this->assertNull(PacienteListadoConsulta::fechaYmd('01/06/2024'));
        $this->assertNull(PacienteListadoConsulta::fechaYmd(''));
    }

    public function test_fecha_vista_efectiva_cae_en_hoy_si_es_invalida(): void
    {
        $this->assertSame(now()->toDateString(), PacienteListadoConsulta::fechaVistaEfectiva(''));
        $this->assertSame('2020-01-15', PacienteListadoConsulta::fechaVistaEfectiva('2020-01-15'));
    }

    public function test_filtro_estado_efectivo(): void
    {
        $this->assertSame(
            PacienteIndex::FILTRO_PENDIENTES,
            PacienteListadoConsulta::filtroEstadoEfectivo(PacienteIndex::FILTRO_PENDIENTES)
        );
        $this->assertSame('', PacienteListadoConsulta::filtroEstadoEfectivo('otro'));
    }

    public function test_encabezados_staff_base(): void
    {
        $exporter = new PacienteListadoExporter;

        $this->assertSame(
            ['#', 'Cliente', 'Fecha', 'Protocolo', 'Nombre', 'Tutor', 'Especie', 'Raza', 'Sexo', 'Edad', 'Precio', 'Estado'],
            $exporter->encabezados([])
        );
    }

    public function test_encabezados_staff_con_columnas_opcionales(): void
    {
        $exporter = new PacienteListadoExporter;

        $this->assertSame(
            ['#', 'Cliente', 'Fecha', 'Protocolo', 'Nombre', 'Tutor', 'L/P', 'Especie', 'Raza', 'Sexo', 'Edad', 'Precio', 'Pagado', 'Cadete', 'Estado'],
            $exporter->encabezados([
                'mostrarListaPrecios' => true,
                'mostrarColumnaPagado' => true,
                'mostrarCadete' => true,
            ])
        );
    }

    public function test_encabezados_autogestion(): void
    {
        $exporter = new PacienteListadoExporter;

        $this->assertSame(
            ['#', 'Fecha', 'Protocolo', 'Nombre', 'Tutor', 'Especie', 'Raza', 'Sexo', 'Edad', 'Estado', 'Precio Lista', 'Desc.', 'Precio c/desc', 'Pagado', 'Saldo'],
            $exporter->encabezados(['autogestion' => true])
        );
    }

    public function test_nombre_archivo_historial_con_rango(): void
    {
        $exporter = new PacienteListadoExporter;
        $resultado = $exporter->buildXlsx([], [
            'vista' => PacienteIndex::VISTA_HISTORIAL,
            'fechaDesde' => '2024-06-01',
            'fechaHasta' => '2024-06-30',
        ]);

        $this->assertSame(
            'pacientes-2024-06-01_2024-06-30-'.now()->format('Y-m-d').'.xlsx',
            $resultado['filename']
        );
    }
}
