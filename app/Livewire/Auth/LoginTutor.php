<?php

namespace App\Livewire\Auth;

use App\Support\LabContext;
use App\Support\Tutor\TutorAcceso;
use App\Support\Tutor\TutorContext;
use App\Support\Tutor\TutorPacientesConsulta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LoginTutor extends Component
{
    public string $dni = '';

    public function mount(): void
    {
        $request = request();

        if ($request->hasAny(['password', 'pwrd'])) {
            session()->flash(
                'error',
                'Por seguridad no se puede iniciar sesión con contraseña en la dirección web. Ingrese sus datos nuevamente.',
            );

            $this->redirectRoute('login.tutor', navigate: false);

            return;
        }

        if ($this->dni === '' && $request->filled('username')) {
            $dni = TutorAcceso::dni((string) $request->query('username'));
            if ($dni !== '') {
                $this->dni = $dni;
            }
        }
    }

    public function updatedDni(string $value): void
    {
        $this->resetErrorBag('dni');
        $normalized = TutorAcceso::dni($value);
        if ($normalized !== $this->dni) {
            $this->dni = $normalized;
        }
    }

    public function rules(): array
    {
        return [
            'dni' => ['required', 'string', 'alpha_num', 'min:1', 'max:'.TutorAcceso::DNI_MAX],
        ];
    }

    public function messages(): array
    {
        return [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.alpha_num' => 'El DNI solo puede contener letras y números.',
            'dni.max' => 'El DNI no puede superar '.TutorAcceso::DNI_MAX.' caracteres.',
        ];
    }

    public function login()
    {
        $this->dni = TutorAcceso::dni($this->dni);
        $this->validate();

        $throttleKey = 'login-tutor:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'dni' => 'Demasiados intentos de acceso. Intente nuevamente en '.RateLimiter::availableIn($throttleKey).' segundos.',
            ]);
        }

        if (! Schema::hasColumn('pacientes', 'dni')) {
            throw ValidationException::withMessages([
                'dni' => 'No se puede iniciar sesión: falta la columna pacientes.dni en este laboratorio.',
            ]);
        }

        if (! TutorPacientesConsulta::tieneEstudios($this->dni)) {
            RateLimiter::hit($throttleKey, 60);
            $this->addError('dni', 'No hay estudios finalizados para este DNI.');

            return null;
        }

        Auth::guard('cliente')->logout();
        Auth::logout();
        LabContext::clear();

        TutorContext::set($this->dni, TutorPacientesConsulta::nombreTutor($this->dni));
        session(['auth.pending_session_regenerate' => true]);
        RateLimiter::clear($throttleKey);

        return $this->redirectRoute('tutor.pacientes', navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login-tutor')
            ->layout('layouts.guest', [
                'brandTitle' => 'Portal del tutor',
                'brandLead' => 'Acceso para tutores de mascotas.',
                'brandNote' => 'Consulte los estudios finalizados de sus mascotas.',
                'documentTitle' => config('app.name').' — Tutor',
            ]);
    }
}
