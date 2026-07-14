<?php

namespace App\Livewire\Abm\Usuarios;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\CuitInput;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UsuarioForm extends Component
{
    public ?int $idUsuarios = null;

    public string $apenom = '';

    public string $dni = '';

    public string $password = '';

    public string $idRoles = '';

    public string $idClientes = '';

    public bool $permisoAfip = false;

    public string $cuit = '';

    public string $razonSocial = '';

    public string $domicComerc = '';

    public string $condIva = '';

    public string $ingresosBrutos = '';

    public string $inicioActiv = '';

    public string $PtoVta = '';

    public string $CbteTipo = '';

    public string $NtaCredTipo = '';

    public string $Concepto = '';

    public string $DocTipo = '';

    public string $CondicionIVAReceptorId = '';

    public string $key = '';

    public string $crt = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::USUARIOS), 403);

        if ($id) {
            $usuario = Usuario::query()->findOrFail($id);
            $this->idUsuarios = $usuario->idUsuarios;
            $this->apenom = (string) $usuario->apenom;
            $this->dni = (string) $usuario->dni;
            $this->password = (string) $usuario->password;
            $this->idRoles = $usuario->idRoles !== null ? (string) $usuario->idRoles : '';
            $this->idClientes = $usuario->idClientes !== null && (int) $usuario->idClientes > 0
                ? (string) $usuario->idClientes
                : '';
            $this->permisoAfip = (int) $usuario->permisoAfip === 1;
            $this->cargarCamposAfip($usuario);
        }
    }

    public function updatedCuit(string $value): void
    {
        $this->resetErrorBag('cuit');
        $this->cuit = CuitInput::format($value);
    }

    public function rules(): array
    {
        $afip = $this->permisoAfip;

        return [
            'apenom' => ['required', 'string', 'max:150'],
            'dni' => [
                'required',
                'string',
                'max:10',
                Rule::unique('usuarios', 'dni')->ignore($this->idUsuarios, 'idUsuarios'),
            ],
            'password' => ['required', 'string', 'max:10'],
            'idRoles' => ['required', 'integer', Rule::exists('roles', 'id')],
            'idClientes' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $v = trim((string) $value);
                    if ($v === '') {
                        return;
                    }
                    if (! ctype_digit($v) || ! Cliente::query()->whereKey((int) $v)->exists()) {
                        $fail('El cliente seleccionado no es válido.');
                    }
                },
            ],
            'permisoAfip' => ['boolean'],
            'cuit' => [
                $afip ? 'required' : 'nullable',
                'string',
                'max:'.CuitInput::FORMATTED_LENGTH,
                function (string $attribute, mixed $value, \Closure $fail) use ($afip): void {
                    $digits = CuitInput::normalize((string) $value);
                    if ($digits === '') {
                        if ($afip) {
                            $fail('El CUIT es obligatorio cuando el permiso AFIP está habilitado.');
                        }

                        return;
                    }
                    if (strlen($digits) !== CuitInput::DIGITS_LENGTH) {
                        $fail('El CUIT debe tener 11 dígitos (formato 99-99999999-9).');
                    }
                },
            ],
            'razonSocial' => [$afip ? 'required' : 'nullable', 'string', 'max:100'],
            'domicComerc' => ['nullable', 'string', 'max:50'],
            'condIva' => ['nullable', 'string', 'max:30'],
            'ingresosBrutos' => ['nullable', 'string', 'max:30'],
            'inicioActiv' => ['nullable', 'date'],
            'PtoVta' => [$afip ? 'required' : 'nullable', 'integer', 'min:0', 'max:99'],
            'CbteTipo' => ['nullable', 'integer', 'min:0', 'max:99'],
            'NtaCredTipo' => ['nullable', 'integer', 'min:0', 'max:99'],
            'Concepto' => ['nullable', 'integer', 'min:0', 'max:99'],
            'DocTipo' => ['nullable', 'integer', 'min:0', 'max:99'],
            'CondicionIVAReceptorId' => ['nullable', 'integer', 'min:0', 'max:99'],
            'key' => ['nullable', 'string', 'max:100'],
            'crt' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'apenom.required' => 'El nombre y apellido es obligatorio.',
            'apenom.max' => 'El nombre no puede superar 150 caracteres.',
            'dni.required' => 'El DNI / usuario de login es obligatorio.',
            'dni.max' => 'El DNI no puede superar 10 caracteres.',
            'dni.unique' => 'Ya existe un usuario con ese DNI.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.max' => 'La contraseña no puede superar 10 caracteres.',
            'idRoles.required' => 'Debe seleccionar un rol.',
            'idRoles.exists' => 'El rol seleccionado no es válido.',
            'cuit.required' => 'El CUIT es obligatorio cuando el permiso AFIP está habilitado.',
            'razonSocial.required' => 'La razón social es obligatoria cuando el permiso AFIP está habilitado.',
            'PtoVta.required' => 'El punto de venta es obligatorio cuando el permiso AFIP está habilitado.',
            'inicioActiv.date' => 'La fecha de inicio de actividades no es válida.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::USUARIOS), 403);

        $key = 'usuario-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $this->normalizarVaciosNumericos();

        $data = $this->validate();

        $idClientesRaw = trim((string) ($data['idClientes'] ?? ''));

        $payload = [
            'apenom' => trim($data['apenom']),
            'dni' => trim($data['dni']),
            'password' => trim($data['password']),
            'idRoles' => (int) $data['idRoles'],
            'idClientes' => $idClientesRaw !== '' ? (int) $idClientesRaw : null,
            'permisoAfip' => $this->permisoAfip ? 1 : 0,
        ];

        $payload = array_merge($payload, $this->payloadAfip($data));

        if ($this->idUsuarios) {
            $usuario = Usuario::query()->findOrFail($this->idUsuarios);
            $usuario->update($payload);
            $mensaje = 'Usuario actualizado correctamente.';
        } else {
            Usuario::query()->create($payload);
            $mensaje = 'Usuario creado correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('abm.usuarios.index', navigate: false);
    }

    private function normalizarVaciosNumericos(): void
    {
        foreach ([
            'PtoVta',
            'CbteTipo',
            'NtaCredTipo',
            'Concepto',
            'DocTipo',
            'CondicionIVAReceptorId',
        ] as $campo) {
            if (trim((string) $this->{$campo}) === '') {
                $this->{$campo} = '0';
            }
        }
    }

    public function render()
    {
        $titulo = $this->idUsuarios ? 'Editar usuario' : 'Nuevo usuario';
        $roles = Rol::query()->orderBy('rol')->get(['id', 'rol']);
        $clientes = Cliente::query()->orderBy('nombre')->get(['idClientes', 'nombre']);

        return view('livewire.abm.usuarios.usuario-form', compact('titulo', 'roles', 'clientes'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function cargarCamposAfip(Usuario $usuario): void
    {
        $this->cuit = $this->valorAfipTexto($usuario->cuit);
        $this->cuit = CuitInput::format($this->cuit);
        $this->razonSocial = $this->valorAfipTexto($usuario->razonSocial);
        $this->domicComerc = $this->valorAfipTexto($usuario->domicComerc);
        $this->condIva = $this->valorAfipTexto($usuario->condIva);
        $this->ingresosBrutos = $this->valorAfipTexto($usuario->ingresosBrutos);
        $this->inicioActiv = $usuario->inicioActiv
            ? substr((string) $usuario->inicioActiv, 0, 10)
            : '';
        $this->PtoVta = (string) ((int) ($usuario->PtoVta ?? 0));
        $this->CbteTipo = (string) ((int) ($usuario->CbteTipo ?? 0));
        $this->NtaCredTipo = (string) ((int) ($usuario->NtaCredTipo ?? 0));
        $this->Concepto = (string) ((int) ($usuario->Concepto ?? 0));
        $this->DocTipo = (string) ((int) ($usuario->DocTipo ?? 0));
        $this->CondicionIVAReceptorId = (string) ((int) ($usuario->CondicionIVAReceptorId ?? 0));
        $this->key = $this->valorAfipTexto($usuario->key);
        $this->crt = $this->valorAfipTexto($usuario->crt);
    }

    /** @param  array<string, mixed>  $data */
    private function payloadAfip(array $data): array
    {
        $cuit = CuitInput::normalize(trim((string) ($data['cuit'] ?? '')));
        $razon = trim((string) ($data['razonSocial'] ?? ''));
        $domic = trim((string) ($data['domicComerc'] ?? ''));
        $cond = trim((string) ($data['condIva'] ?? ''));
        $iibb = trim((string) ($data['ingresosBrutos'] ?? ''));
        $inicio = trim((string) ($data['inicioActiv'] ?? ''));
        $key = trim((string) ($data['key'] ?? ''));
        $crt = trim((string) ($data['crt'] ?? ''));

        return [
            'cuit' => $cuit !== '' ? $cuit : '0',
            'razonSocial' => $razon !== '' ? $razon : '0',
            'domicComerc' => $domic !== '' ? $domic : '0',
            'condIva' => $cond !== '' ? $cond : '0',
            'ingresosBrutos' => $iibb !== '' ? $iibb : '0',
            'inicioActiv' => $inicio !== '' ? $inicio : null,
            'PtoVta' => (int) ($data['PtoVta'] ?? 0),
            'CbteTipo' => (int) ($data['CbteTipo'] ?? 0),
            'NtaCredTipo' => (int) ($data['NtaCredTipo'] ?? 0),
            'Concepto' => (int) ($data['Concepto'] ?? 0),
            'DocTipo' => (int) ($data['DocTipo'] ?? 0),
            'CondicionIVAReceptorId' => (int) ($data['CondicionIVAReceptorId'] ?? 0),
            'key' => $key !== '' ? $key : '0',
            'crt' => $crt !== '' ? $crt : '0',
        ];
    }

    private function valorAfipTexto(mixed $value): string
    {
        $texto = trim((string) ($value ?? ''));

        return ($texto === '' || $texto === '0') ? '' : $texto;
    }
}
