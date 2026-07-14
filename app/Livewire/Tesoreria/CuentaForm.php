<?php

namespace App\Livewire\Tesoreria;

use App\Models\Cuenta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class CuentaForm extends Component
{
    public ?int $idCuenta = null;

    public string $nombreCuenta = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        if ($id) {
            $cuenta = Cuenta::query()->findOrFail($id);
            $this->idCuenta = (int) $cuenta->id;
            $this->nombreCuenta = (string) ($cuenta->nombreCuenta ?? '');
        }
    }

    public function rules(): array
    {
        return [
            'nombreCuenta' => ['required', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombreCuenta.required' => 'El nombre de la cuenta es obligatorio.',
            'nombreCuenta.max' => 'El nombre no puede superar 80 caracteres.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $key = 'cuenta-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $data = $this->validate();
        $data['nombreCuenta'] = trim($data['nombreCuenta']);

        if ($this->idCuenta) {
            $cuenta = Cuenta::query()->findOrFail($this->idCuenta);
            $cuenta->update($data);
            $mensaje = 'Cuenta actualizada correctamente.';
        } else {
            Cuenta::query()->create($data);
            $mensaje = 'Cuenta creada correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('tesoreria.cuentas.index', navigate: false);
    }

    public function render()
    {
        $titulo = $this->idCuenta ? 'Editar cuenta contable' : 'Nueva cuenta contable';

        return view('livewire.tesoreria.cuenta-form', compact('titulo'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
