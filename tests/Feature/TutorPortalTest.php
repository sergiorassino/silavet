<?php

namespace Tests\Feature;

use Tests\TestCase;

class TutorPortalTest extends TestCase
{
    public function test_login_tutor_muestra_el_formulario(): void
    {
        $this->get('/loginTutor')
            ->assertOk()
            ->assertSee('Portal del tutor')
            ->assertSee('Ingrese el DNI del tutor para ver los estudios de sus mascotas.')
            ->assertDontSee('Contraseña')
            ->assertSee('Ingresar');
    }

    public function test_login_de_personal_conserva_su_texto(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Portal del personal')
            ->assertSee('Ingrese su DNI y contraseña para acceder al laboratorio.');
    }

    public function test_grilla_sin_sesion_vuelve_al_login(): void
    {
        $this->get('/tutor/pacientes')
            ->assertRedirect(route('login.tutor'));
    }

    public function test_grilla_logueada_muestra_solo_datos_del_paciente(): void
    {
        $this->withSession([
            'tutor.dni' => 'ZZZZZZZZ',
            'tutor.nombre' => 'Tutor Prueba',
        ])->get('/tutor/pacientes')
            ->assertOk()
            ->assertSee('Autogestión del tutor')
            ->assertSee('Estudios finalizados de Tutor Prueba.')
            ->assertDontSee('estado Final o Final/Env')
            ->assertSee('Tutor Prueba')
            ->assertSee('INFOR.')
            ->assertDontSee('ESTADO')
            ->assertDontSee('Precio Lista')
            ->assertDontSee('Asistente IA')
            ->assertDontSee('DETERM.');
    }
}
