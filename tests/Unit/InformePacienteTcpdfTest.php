<?php

namespace Tests\Unit;

use App\Support\Informes\InformePacienteTcpdf;
use TCPDF;
use Tests\TestCase;

class InformePacienteTcpdfTest extends TestCase
{
    /** @var list<string> */
    private array $temporales = [];

    protected function tearDown(): void
    {
        foreach ($this->temporales as $ruta) {
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }

        parent::tearDown();
    }

    public function test_sin_adjunto_queda_en_una_pagina(): void
    {
        $pdf = InformePacienteTcpdf::generar($this->datosInforme());

        $this->assertSame(1, $pdf->getNumPages());
    }

    public function test_incorpora_paginas_del_pdf_adjunto_al_final(): void
    {
        $adjunto = $this->pdfTemporalDePrueba(2);
        $pdf = InformePacienteTcpdf::generar($this->datosInforme([
            'adjunto_ruta' => $adjunto,
            'adjunto_nombre' => basename($adjunto),
        ]));

        $this->assertSame(3, $pdf->getNumPages());
    }

    public function test_filas_habituales_y_texto_corto_siguen_en_una_pagina(): void
    {
        $pdf = InformePacienteTcpdf::generar($this->datosInforme([
            'grupos' => [[
                'idGrupos' => 1,
                'nombreGrupo' => 'Hemograma',
                'mostrarReferencias' => 1,
                'renglones' => [
                    [
                        'tipoItem' => 1,
                        'nombreItem' => 'Hemoglobina',
                        'valor' => '12,5',
                        'unidadMedida' => 'g/dL',
                        'referencia' => '12 - 18',
                    ],
                    [
                        'tipoItem' => 9,
                        'nombreItem' => 'Linfocitos',
                        'valor' => '30',
                        'unidadMedida' => '%',
                        'valor2' => '1,2',
                        'unidadMedida2' => '10^9/L',
                        'referencia' => '1 - 4',
                    ],
                    [
                        'tipoItem' => 8,
                        'nombreItem' => 'Comentario',
                        'valor' => "Texto breve de control.\nSegunda linea.",
                    ],
                ],
            ]],
        ]));

        $this->assertSame(1, $pdf->getNumPages());
    }

    public function test_texto_largo_que_entra_en_la_hoja_siguiente_no_se_reparte(): void
    {
        $pdf = InformePacienteTcpdf::generar($this->informeConTextoLargo($this->textoDeLineas(45)));

        $this->assertSame(3, $pdf->getNumPages());
    }

    public function test_texto_largo_que_no_entra_en_una_hoja_sigue_en_las_siguientes(): void
    {
        $queEntra = InformePacienteTcpdf::generar($this->informeConTextoLargo($this->textoDeLineas(45)));
        $lineas = InformePacienteTcpdf::generar($this->informeConTextoLargo($this->textoDeLineas(220)));
        $parrafo = InformePacienteTcpdf::generar($this->informeConTextoLargo(
            trim(str_repeat('informe citologico detallado ', 500))
        ));

        $this->assertGreaterThan($queEntra->getNumPages(), $lineas->getNumPages());
        $this->assertGreaterThanOrEqual(4, $lineas->getNumPages());
        $this->assertGreaterThanOrEqual(4, $parrafo->getNumPages());
    }

    public function test_adjunto_declarado_pero_ausente_agrega_pagina_de_aviso(): void
    {
        $pdf = InformePacienteTcpdf::generar($this->datosInforme([
            'adjunto_ruta' => null,
            'adjunto_nombre' => 'faltante.pdf',
        ]));

        $this->assertSame(2, $pdf->getNumPages());
        $binario = $pdf->Output('test.pdf', 'S');
        $this->assertStringStartsWith('%PDF', $binario);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function datosInforme(array $extra = []): array
    {
        return array_merge([
            'paciente' => [
                'idPacientes' => 1,
                'protocolo' => 'TEST-1',
                'fecha' => '03/09/2026',
                'nombre' => 'Prueba',
                'propietario' => '',
                'sexo' => '',
                'edad' => '',
                'especie' => '',
                'raza' => '',
                'cliente' => '',
                'medico_solicitante' => '',
                'observaciones' => '',
                'idEspecies' => 1,
                'rotulo_ref' => '',
            ],
            'header' => [
                'nombre' => 'Lab',
                'direccion' => '',
                'telefono' => '',
                'email' => '',
                'logo_file' => null,
                'header_file' => null,
            ],
            'color_rgb' => [103, 29, 143],
            'footer' => [],
            'grupos' => [
                [
                    'idGrupos' => 1,
                    'nombreGrupo' => 'Hemograma',
                    'mostrarReferencias' => 1,
                    'renglones' => [],
                ],
            ],
            'adjunto_ruta' => null,
            'adjunto_nombre' => '',
        ], $extra);
    }

    /**
     * @return array<string, mixed>
     */
    private function informeConTextoLargo(string $valor): array
    {
        return $this->datosInforme([
            'grupos' => [[
                'idGrupos' => 1,
                'nombreGrupo' => 'Citologia',
                'mostrarReferencias' => 0,
                'renglones' => [[
                    'tipoItem' => 8,
                    'nombreItem' => 'Informe',
                    'valor' => $valor,
                ]],
            ]],
        ]);
    }

    private function textoDeLineas(int $lineas): string
    {
        $filas = [];
        for ($i = 1; $i <= $lineas; $i++) {
            $filas[] = 'Linea '.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
        }

        return implode("\n", $filas);
    }

    private function pdfTemporalDePrueba(int $paginas): string
    {
        $src = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $src->setPrintHeader(false);
        $src->setPrintFooter(false);
        $src->SetAutoPageBreak(false);
        for ($n = 1; $n <= $paginas; $n++) {
            $src->AddPage();
            $src->SetFont('helvetica', '', 12);
            $src->Cell(0, 10, 'Pagina adjunto '.$n, 0, 1);
        }

        $ruta = sys_get_temp_dir().DIRECTORY_SEPARATOR.'silavet_adjunto_'.bin2hex(random_bytes(4)).'.pdf';
        $src->Output($ruta, 'F');
        $this->temporales[] = $ruta;

        return $ruta;
    }
}
