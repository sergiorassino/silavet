<?php

namespace App\Livewire\Abm\Especies;

use App\Models\Especie;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EspecieForm extends Component
{
    public ?int $idEspecies = null;

    public string $nombre = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::ESPECIES), 403);

        if ($id) {
            $especie = Especie::findOrFail($id);
            $this->idEspecies = $especie->idEspecies;
            $this->nombre = (string) $especie->nombre;
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:50',
                Rule::unique('especies', 'nombre')->ignore($this->idEspecies, 'idEspecies'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la especie es obligatorio.',
            'nombre.max' => 'El nombre no puede superar 50 caracteres.',
            'nombre.unique' => 'Ya existe una especie con ese nombre.',
        ];
    }

    public function save(): void
    {
        $key = 'especie-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $data = $this->validate();
        $data['nombre'] = trim($data['nombre']);

        if ($this->idEspecies) {
            $especie = Especie::findOrFail($this->idEspecies);
            $especie->update($data);
            $mensaje = 'Especie actualizada correctamente.';
        } else {
            Especie::create($data);
            $mensaje = 'Especie creada correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('abm.especies.index', navigate: false);
    }

    public function render()
    {
        $titulo = $this->idEspecies ? 'Editar especie' : 'Nueva especie';

        return view('livewire.abm.especies.especie-form', compact('titulo'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
