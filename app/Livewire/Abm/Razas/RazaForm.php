<?php

namespace App\Livewire\Abm\Razas;

use App\Models\Especie;
use App\Models\Raza;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;

class RazaForm extends Component
{
    public ?int $idRazas = null;

    public ?int $idEspecies = null;

    public string $nombre = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::ESPECIES), 403);

        if ($id) {
            $raza = Raza::findOrFail($id);
            $this->idRazas = $raza->idRazas;
            $this->idEspecies = $raza->idEspecies ? (int) $raza->idEspecies : null;
            $this->nombre = (string) ($raza->nombre ?? '');
        }
    }

    public function rules(): array
    {
        return [
            'idEspecies' => ['required', 'integer', 'exists:especies,idEspecies'],
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('razas', 'nombre')
                    ->where(fn ($q) => $q->where('idEspecies', $this->idEspecies))
                    ->ignore($this->idRazas, 'idRazas'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'idEspecies.required' => 'La especie es obligatoria.',
            'idEspecies.exists' => 'La especie seleccionada no es válida.',
            'nombre.required' => 'El nombre de la raza es obligatorio.',
            'nombre.max' => 'El nombre no puede superar 150 caracteres.',
            'nombre.unique' => 'Ya existe una raza con ese nombre para la especie seleccionada.',
        ];
    }

    public function save(): void
    {
        $key = 'raza-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $data = $this->validate();
        $data['nombre'] = trim($data['nombre']);
        $data['idEspecies'] = (int) $data['idEspecies'];

        if ($this->idRazas) {
            $raza = Raza::findOrFail($this->idRazas);
            $raza->update($data);
            $mensaje = 'Raza actualizada correctamente.';
        } else {
            Raza::create($data);
            $mensaje = 'Raza creada correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('abm.razas.index', navigate: false);
    }

    public function render()
    {
        $titulo = $this->idRazas ? 'Editar raza' : 'Nueva raza';
        $especies = Especie::query()
            ->orderBy('nombre')
            ->get(['idEspecies', 'nombre']);

        return view('livewire.abm.razas.raza-form', compact('titulo', 'especies'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
