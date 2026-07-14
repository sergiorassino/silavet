<?php

namespace App\Livewire\Abm\Derivaciones;

use App\Models\Derivacion;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;

class DerivacionForm extends Component
{
    public ?int $idDerivaciones = null;

    public string $derivacion = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        if ($id) {
            $centro = Derivacion::findOrFail($id);
            $this->idDerivaciones = $centro->idDerivaciones;
            $this->derivacion = (string) $centro->derivacion;
        }
    }

    public function rules(): array
    {
        return [
            'derivacion' => [
                'required',
                'string',
                'max:50',
                Rule::unique('derivaciones', 'derivacion')->ignore($this->idDerivaciones, 'idDerivaciones'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'derivacion.required' => 'El nombre del centro es obligatorio.',
            'derivacion.max' => 'El nombre no puede superar 50 caracteres.',
            'derivacion.unique' => 'Ya existe un centro de derivación con ese nombre.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'derivacion-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $data = $this->validate();
        $data['derivacion'] = trim($data['derivacion']);

        if ($this->idDerivaciones) {
            $centro = Derivacion::findOrFail($this->idDerivaciones);
            $centro->update($data);
            $mensaje = 'Centro de derivación actualizado correctamente.';
        } else {
            Derivacion::create($data);
            $mensaje = 'Centro de derivación creado correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('abm.derivaciones.index', navigate: false);
    }

    public function render()
    {
        $titulo = $this->idDerivaciones ? 'Editar centro de derivación' : 'Nuevo centro de derivación';

        return view('livewire.abm.derivaciones.derivacion-form', compact('titulo'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
