<?php

namespace Tests\Unit;

use App\Support\Protocolos\HojaRutaHemogramaConfig;
use App\Support\Protocolos\HojaRutaHemogramaConsulta;
use App\Support\Protocolos\HojaRutaHemogramaTcpdf;
use Tests\TestCase;

class HojaRutaHemogramaTest extends TestCase
{
    public function test_activo_por_defecto(): void
    {
        config(['tenant.hoja_ruta_hemograma.activo' => true]);

        $this->assertTrue(HojaRutaHemogramaConfig::activo());
    }

    public function test_inactivo_oculta_el_modulo(): void
    {
        config(['tenant.hoja_ruta_hemograma.activo' => false]);

        $this->assertFalse(HojaRutaHemogramaConfig::activo());
    }

    public function test_citologias_ocultas_por_defecto(): void
    {
        config(['tenant.hoja_ruta_hemograma.mostrar_citologias' => false]);

        $this->assertFalse(HojaRutaHemogramaConfig::mostrarCitologias());
        $this->assertSame(114, HojaRutaHemogramaConfig::idEspecial('liq_puncion'));
        $this->assertSame(141, HojaRutaHemogramaConfig::idEspecial('cit_oido'));
        $this->assertSame(142, HojaRutaHemogramaConfig::idEspecial('cit_vaginal'));
        $this->assertSame(194, HojaRutaHemogramaConfig::idEspecial('cit_piel'));
        $this->assertSame('Líq.Punción', HojaRutaHemogramaConfig::tituloCitologia('liq_puncion'));
    }

    public function test_citologias_se_pueden_activar(): void
    {
        config(['tenant.hoja_ruta_hemograma.mostrar_citologias' => true]);

        $this->assertTrue(HojaRutaHemogramaConfig::mostrarCitologias());
    }

    public function test_columnas_usan_ids_del_tenant(): void
    {
        config([
            'tenant.hoja_ruta_hemograma.columnas' => [
                'wbc' => 6,
                'lym' => 7,
                'pt' => 21,
            ],
        ]);

        $columnas = HojaRutaHemogramaConfig::columnas();

        $this->assertSame('WBC', $columnas[0]['titulo']);
        $this->assertSame(6, $columnas[0]['id']);
        $this->assertSame(HojaRutaHemogramaConfig::ENCABEZADO_AMARILLO, $columnas[0]['encabezado']);
        $this->assertSame('PT', $columnas[15]['titulo']);
        $this->assertSame(21, $columnas[15]['id']);
        $this->assertSame(HojaRutaHemogramaConfig::ENCABEZADO_AZUL, $columnas[15]['encabezado']);
    }

    public function test_item_pedido_exige_id_positivo(): void
    {
        $this->assertFalse(HojaRutaHemogramaConfig::itemPedido(0, [0, 6]));
        $this->assertFalse(HojaRutaHemogramaConfig::itemPedido(6, [7, 8]));
        $this->assertTrue(HojaRutaHemogramaConfig::itemPedido(6, [6, 7]));
    }

    public function test_normaliza_fecha_invalida_a_hoy(): void
    {
        $this->assertSame('2026-08-31', HojaRutaHemogramaConsulta::normalizarFecha('2026-08-31'));
        $this->assertSame(now()->toDateString(), HojaRutaHemogramaConsulta::normalizarFecha('31/08/2026'));
        $this->assertSame('31/08/2026', HojaRutaHemogramaConsulta::fechaTexto('2026-08-31'));
    }

    public function test_abreviar_cliente_respeta_tope(): void
    {
        $this->assertSame('Corta', HojaRutaHemogramaConsulta::abreviar('Corta', 28));
        $this->assertSame(
            'Veterinaria San Francisco d…',
            HojaRutaHemogramaConsulta::abreviar('Veterinaria San Francisco de Asís', 28),
        );
    }

    public function test_lineas_identificacion_agrupan_campos(): void
    {
        $lineas = HojaRutaHemogramaConsulta::lineasIdentificacion([
            'nombreProtocolo' => '260829001',
            'nombre' => 'Minino',
            'especie' => 'Felino',
            'raza' => 'Persa',
            'sexo' => 'Macho',
            'edad' => '5 a',
            'cliente' => 'Clínica San Roque',
        ]);

        $this->assertSame([
            '260829001',
            'Minino',
            'Felino · Persa',
            'Macho · 5 a',
            'Clínica San Roque',
        ], $lineas);
    }

    public function test_lineas_identificacion_omite_vacios(): void
    {
        $lineas = HojaRutaHemogramaConsulta::lineasIdentificacion([
            'nombreProtocolo' => '1',
            'nombre' => 'Luna',
            'especie' => 'Canino',
            'raza' => '',
            'sexo' => '',
            'edad' => '2 a',
            'cliente' => '',
        ]);

        $this->assertSame(['1', 'Luna', 'Canino', '2 a'], $lineas);
    }

    public function test_pdf_vacio_es_una_pagina(): void
    {
        $pdf = HojaRutaHemogramaTcpdf::generar([
            'fecha_texto' => '31/08/2026',
            'filas' => [],
        ]);

        $this->assertSame(1, $pdf->getNumPages());
        $binario = $pdf->Output('test.pdf', 'S');
        $this->assertStringStartsWith('%PDF', $binario);
    }

    public function test_pdf_no_agrega_pagina_vacia_al_noveno(): void
    {
        $filas = [];
        for ($i = 1; $i <= 9; $i++) {
            $filas[] = [
                'nombreProtocolo' => 'P'.$i,
                'nombre' => 'N'.$i,
                'especie' => 'Canino',
                'raza' => 'Mestizo',
                'sexo' => 'Macho',
                'edad' => '3 a',
                'cliente' => 'Veterinaria Ejemplo Muy Larga Nombre',
                'detPedidas' => [6, 1],
            ];
        }

        $pdf = HojaRutaHemogramaTcpdf::generar([
            'fecha_texto' => '31/08/2026',
            'filas' => $filas,
        ]);

        $this->assertSame(1, $pdf->getNumPages());
    }

    public function test_pdf_decimo_protocolo_abre_segunda_pagina(): void
    {
        $filas = [];
        for ($i = 1; $i <= 10; $i++) {
            $filas[] = [
                'nombreProtocolo' => 'P'.$i,
                'nombre' => 'N'.$i,
                'especie' => 'Canin',
                'detPedidas' => [],
            ];
        }

        $pdf = HojaRutaHemogramaTcpdf::generar([
            'fecha_texto' => '31/08/2026',
            'filas' => $filas,
        ]);

        $this->assertSame(2, $pdf->getNumPages());
    }

    public function test_pdf_con_citologias_respeta_paginado(): void
    {
        config(['tenant.hoja_ruta_hemograma.mostrar_citologias' => true]);

        $filas = [];
        for ($i = 1; $i <= 9; $i++) {
            $filas[] = [
                'nombreProtocolo' => 'P'.$i,
                'nombre' => 'N'.$i,
                'especie' => 'Canino',
                'detPedidas' => [114, 141, 142, 194],
            ];
        }

        $pdfNueve = HojaRutaHemogramaTcpdf::generar([
            'fecha_texto' => '31/08/2026',
            'filas' => $filas,
        ]);
        $this->assertSame(1, $pdfNueve->getNumPages());

        $filas[] = [
            'nombreProtocolo' => 'P10',
            'nombre' => 'N10',
            'especie' => 'Felino',
            'detPedidas' => [],
        ];
        $pdfDiez = HojaRutaHemogramaTcpdf::generar([
            'fecha_texto' => '31/08/2026',
            'filas' => $filas,
        ]);
        $this->assertSame(2, $pdfDiez->getNumPages());
    }
}
