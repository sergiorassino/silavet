<?php

namespace App\Livewire\Abm\Reactivos;

use App\Models\Reactivo;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ReactivoForm extends Component
{
    public ?int $idReactivo = null;

    public string $reactivo = '';

    public string $cantidad = '';

    public string $minAviso = '';

    public string $existIdeal = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::REACTIVOS), 403);

        if ($id) {
            $registro = Reactivo::findOrFail($id);
            $this->idReactivo  = (int) $registro->id;
            $this->reactivo    = (string) $registro->reactivo;
            $this->cantidad    = (string) $registro->cantidad;
            $this->minAviso    = (string) $registro->minAviso;
            $this->existIdeal  = (string) $registro->existIdeal;
        }
    }

    public function rules(): array
    {
        return [
            'reactivo'   => [
                'required',
                'string',
                'max:50',
                Rule::unique('reactivos', 'reactivo')->ignore($this->idReactivo, 'id'),
            ],
            'cantidad'   => ['required', 'integer', 'min:0'],
            'minAviso'   => ['required', 'integer', 'min:0'],
            'existIdeal' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'reactivo.required'   => 'El nombre del reactivo es obligatorio.',
            'reactivo.max'        => 'El nombre no puede superar 50 caracteres.',
            'reactivo.unique'     => 'Ya existe un reactivo con ese nombre.',
            'cantidad.required'   => 'La cantidad es obligatoria.',
            'cantidad.integer'    => 'La cantidad debe ser un número entero.',
            'cantidad.min'        => 'La cantidad no puede ser negativa.',
            'minAviso.required'   => 'El mínimo para aviso es obligatorio.',
            'minAviso.integer'    => 'El mínimo debe ser un número entero.',
            'minAviso.min'        => 'El mínimo no puede ser negativo.',
            'existIdeal.required' => 'La existencia ideal es obligatoria.',
            'existIdeal.integer'  => 'La existencia ideal debe ser un número entero.',
            'existIdeal.min'      => 'La existencia ideal no puede ser negativa.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::REACTIVOS), 403);

        $key = 'reactivo-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $data = $this->validate();
        $data['reactivo'] = trim($data['reactivo']);

        if ($this->idReactivo) {
            $registro = Reactivo::findOrFail($this->idReactivo);
            $registro->update($data);
            $mensaje = 'Reactivo actualizado correctamente.';
        } else {
            Reactivo::create($data);
            $mensaje = 'Reactivo creado correctamente.';
        }

        $this->dispatch('vl-swal-exito', mensaje: $mensaje);
        $this->redirectRoute('abm.reactivos.index', navigate: false);
    }

    public function render()
    {
        $tituloPagina = $this->idReactivo ? 'Editar reactivo' : 'Nuevo reactivo';

        return view('livewire.abm.reactivos.reactivo-form', compact('tituloPagina'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
