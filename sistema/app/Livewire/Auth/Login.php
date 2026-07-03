<?php

namespace App\Livewire\Auth;

use App\Models\Usuario;
use App\Support\DniInput;
use App\Support\LabContext;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $dni = '';

    public string $password = '';

    public function mount(): void
    {
        if (request()->hasAny(['password', 'pwrd'])) {
            session()->flash(
                'error',
                'Por seguridad no se puede iniciar sesión con contraseña en la dirección web.',
            );

            $this->redirectRoute('login', navigate: false);
        }
    }

    public function updatedDni(string $value): void
    {
        $this->resetErrorBag('dni');
        $digits = DniInput::digitsOnly($value);
        if ($digits !== $this->dni) {
            $this->dni = $digits;
        }
    }

    public function rules(): array
    {
        return [
            'dni' => ['required', 'digits_between:7,11'],
            'password' => ['required', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.digits_between' => 'El DNI debe tener entre 7 y 11 dígitos.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }

    public function login()
    {
        $this->dni = DniInput::digitsOnly($this->dni);
        $this->validate();

        $throttleKey = 'login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'dni' => 'Demasiados intentos. Intente nuevamente en '.RateLimiter::availableIn($throttleKey).' segundos.',
            ]);
        }

        $credentials = [
            'dni' => $this->dni,
            'password' => $this->password,
            'portal' => 'staff',
        ];

        if (Auth::attempt($credentials, false)) {
            /** @var Usuario $usuario */
            $usuario = Auth::user();

            session(['auth.pending_session_regenerate' => true]);

            LabContext::set(
                idUsuarios: (int) $usuario->idUsuarios,
                idRoles: $usuario->idRoles ? (int) $usuario->idRoles : null,
                idClientes: $usuario->idClientes ? (int) $usuario->idClientes : null,
            );

            RateLimiter::clear($throttleKey);

            return $this->redirectRoute(
                UsuarioMenuPortal::rutaInicio($usuario->idRoles, $usuario->idClientes),
                navigate: false,
            );
        }

        RateLimiter::hit($throttleKey, 60);
        $this->addError('dni', 'DNI o contraseña incorrectos.');
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.guest');
    }
}
