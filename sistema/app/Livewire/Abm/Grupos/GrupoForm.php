<?php

namespace App\Livewire\Abm\Grupos;

use App\Models\Grupo;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class GrupoForm extends Component
{
    public ?int $idGrupos = null;

    public string $nombreGrupo = '';

    public string $orden = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        if ($id) {
            $grupo = Grupo::findOrFail($id);
            $this->idGrupos = $grupo->idGrupos;
            $this->nombreGrupo = (string) $grupo->nombreGrupo;
            $this->orden = $grupo->orden !== null ? (string) $grupo->orden : '';
        }
    }

    public function rules(): array
    {
        return [
            'nombreGrupo' => ['required', 'string', 'max:50'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombreGrupo.required' => 'El nombre del grupo es obligatorio.',
            'nombreGrupo.max' => 'El nombre no puede superar 50 caracteres.',
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden no puede ser negativo.',
            'orden.max' => 'El orden no puede superar 9999.',
        ];
    }

    public function save(): void
    {
        $key = 'grupo-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $data = $this->validate();
        $data['nombreGrupo'] = trim($data['nombreGrupo']);
        $orden = trim((string) ($data['orden'] ?? ''));
        $data['orden'] = $orden === '' ? null : (int) $orden;

        if ($this->idGrupos) {
            $grupo = Grupo::findOrFail($this->idGrupos);
            $grupo->update($data);
            $mensaje = 'Grupo actualizado correctamente.';
        } else {
            Grupo::create($data);
            $mensaje = 'Grupo creado correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('admin.grupos.index', navigate: false);
    }

    public function render()
    {
        $titulo = $this->idGrupos ? 'Editar grupo' : 'Nuevo grupo';

        return view('livewire.abm.grupos.grupo-form', compact('titulo'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
