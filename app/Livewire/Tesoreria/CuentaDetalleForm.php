<?php

namespace App\Livewire\Tesoreria;

use App\Models\Cuenta;
use App\Models\CuentaDetalle;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CuentaDetalleForm extends Component
{
    public ?int $idCuentaDetalle = null;

    public ?int $idCuentas = null;

    public string $nombreCuentasDetalle = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        if ($id) {
            $detalle = CuentaDetalle::query()->findOrFail($id);
            $this->idCuentaDetalle = (int) $detalle->id;
            $this->idCuentas = $detalle->idCuentas !== null ? (int) $detalle->idCuentas : null;
            $this->nombreCuentasDetalle = (string) ($detalle->nombreCuentasDetalle ?? '');
        }
    }

    public function rules(): array
    {
        return [
            'idCuentas' => ['required', 'integer', Rule::exists('cuentas', 'id')],
            'nombreCuentasDetalle' => ['required', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'idCuentas.required' => 'Debe seleccionar la cuenta contable.',
            'idCuentas.exists' => 'La cuenta seleccionada no es válida.',
            'nombreCuentasDetalle.required' => 'El nombre de la subcuenta es obligatorio.',
            'nombreCuentasDetalle.max' => 'El nombre no puede superar 80 caracteres.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $key = 'cuenta-detalle-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $data = $this->validate();
        $data['nombreCuentasDetalle'] = trim($data['nombreCuentasDetalle']);
        $data['idCuentas'] = (int) $data['idCuentas'];

        if ($this->idCuentaDetalle) {
            $detalle = CuentaDetalle::query()->findOrFail($this->idCuentaDetalle);
            $detalle->update($data);
            $mensaje = 'Subcuenta actualizada correctamente.';
        } else {
            CuentaDetalle::query()->create($data);
            $mensaje = 'Subcuenta creada correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('tesoreria.cuentas-detalle.index', navigate: false);
    }

    public function render()
    {
        $titulo = $this->idCuentaDetalle ? 'Editar cuenta detalle' : 'Nueva cuenta detalle';
        $cuentas = Cuenta::query()->orderBy('nombreCuenta')->get(['id', 'nombreCuenta']);

        return view('livewire.tesoreria.cuenta-detalle-form', compact('titulo', 'cuentas'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
