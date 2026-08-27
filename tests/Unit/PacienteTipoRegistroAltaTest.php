<?php

namespace Tests\Unit;

use App\Models\Paciente;
use ReflectionMethod;
use Tests\TestCase;

class PacienteTipoRegistroAltaTest extends TestCase
{
    public function test_creating_asigna_protocolo_si_falta_tipo_registro(): void
    {
        $paciente = new Paciente;
        $paciente->fill(['nombre' => 'Rex']);

        $this->fireCreating($paciente);

        $this->assertSame(Paciente::TIPO_PROTOCOLO, (int) $paciente->tipoRegistro);
    }

    public function test_creating_convierte_cero_en_protocolo(): void
    {
        $paciente = new Paciente;
        $paciente->fill([
            'nombre' => 'Rex',
            'tipoRegistro' => 0,
        ]);

        $this->fireCreating($paciente);

        $this->assertSame(Paciente::TIPO_PROTOCOLO, (int) $paciente->tipoRegistro);
    }

    public function test_creating_respeta_ingreso_y_egreso(): void
    {
        $ingreso = new Paciente;
        $ingreso->fill(['tipoRegistro' => Paciente::TIPO_INGRESO]);
        $this->fireCreating($ingreso);
        $this->assertSame(Paciente::TIPO_INGRESO, (int) $ingreso->tipoRegistro);

        $egreso = new Paciente;
        $egreso->fill(['tipoRegistro' => Paciente::TIPO_EGRESO]);
        $this->fireCreating($egreso);
        $this->assertSame(Paciente::TIPO_EGRESO, (int) $egreso->tipoRegistro);
    }

    private function fireCreating(Paciente $paciente): void
    {
        $method = new ReflectionMethod(Paciente::class, 'fireModelEvent');
        $method->invoke($paciente, 'creating');
    }
}
