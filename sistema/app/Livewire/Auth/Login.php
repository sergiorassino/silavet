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
        $request = request();

        if ($request->hasAny(['password', 'pwrd'])) {
            session()->flash(
                'error',
                'Por seguridad no se puede iniciar sesión con contraseña en la dirección web. Ingrese sus datos nuevamente.',
            );

            $this->redirectRoute('login', navigate: false);

            return;
        }

        if ($this->dni === '' && $request->filled('username')) {
            $dni = DniInput::normalize((string) $request->query('username'));
            if ($dni !== '') {
                $this->dni = $dni;
            }
        }
    }

    public function updatedDni(string $value): void
    {
        $this->resetErrorBag('dni');
        $normalized = DniInput::normalize($value);
        if ($normalized !== $this->dni) {
            $this->dni = $normalized;
        }
    }

    public function updatedPassword(): void
    {
        $this->resetErrorBag('dni');
    }

    public function rules(): array
    {
        return [
            'dni' => ['required', 'string', 'alpha_num', 'min:1', 'max:'.DniInput::MAX_LENGTH],
            'password' => ['required', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'dni.required' => 'El usuario es obligatorio.',
            'dni.alpha_num' => 'El usuario solo puede contener letras y números.',
            'dni.max' => 'El usuario no puede superar '.DniInput::MAX_LENGTH.' caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }

    public function login()
    {
        $this->dni = DniInput::normalize($this->dni);
        $this->validate();

        $throttleKey = 'login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'dni' => 'Demasiados intentos de acceso. Intente nuevamente en '.RateLimiter::availableIn($throttleKey).' segundos.',
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
        $this->addError('dni', 'DNI o contraseña incorrectos. Verifique sus datos.');
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.guest');
    }
}
