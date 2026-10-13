<?php

namespace Tests\Unit;

use App\Support\Resultados\ResultadosEstadosCatalog;
use App\Support\Tutor\TutorAcceso;
use App\Support\Tutor\TutorPacientesConsulta;
use Tests\TestCase;

class TutorAccesoTest extends TestCase
{
    public function test_dni_normaliza_y_recorta_a_ocho(): void
    {
        $this->assertSame('25038868', TutorAcceso::dni('25.038.868'));
        $this->assertSame('12345678', TutorAcceso::dni('123456789'));
    }

    public function test_estados_visibles_son_final_y_final_env(): void
    {
        $this->assertSame(
            [ResultadosEstadosCatalog::FINAL, ResultadosEstadosCatalog::FINAL_ENV],
            TutorAcceso::estadosVisibles(),
        );
    }

    public function test_fecha_ymd_rechaza_valores_invalidos(): void
    {
        $this->assertSame('2026-10-09', TutorPacientesConsulta::fechaYmd('2026-10-09'));
        $this->assertNull(TutorPacientesConsulta::fechaYmd('09/10/2026'));
        $this->assertNull(TutorPacientesConsulta::fechaYmd('2026-02-31'));
        $this->assertNull(TutorPacientesConsulta::fechaYmd(''));
    }
}
