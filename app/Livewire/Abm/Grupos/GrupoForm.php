<?php

namespace App\Livewire\Abm\Grupos;

use App\Models\Grupo;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class GrupoForm extends Component
{
    public ?int $idGrupos = null;

    public string $nombreGrupo = '';

    public string $orden = '';

    /** 1 = mostrar encabezado VALORES DE REFERENCIA en el PDF; 0 = no. */
    public string $mostrarReferencias = '1';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        if ($id) {
            $grupo = Grupo::findOrFail($id);
            $this->idGrupos = $grupo->idGrupos;
            $this->nombreGrupo = (string) $grupo->nombreGrupo;
            $this->orden = $grupo->orden !== null ? (string) $grupo->orden : '';
            if (Grupo::tieneColumnaMostrarReferencias()) {
                $this->mostrarReferencias = ((int) ($grupo->mostrarReferencias ?? 1)) === 1 ? '1' : '0';
            }
        }
    }

    public function rules(): array
    {
        return [
            'nombreGrupo' => ['required', 'string', 'max:50'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'mostrarReferencias' => ['required', 'in:0,1'],
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
            'mostrarReferencias.required' => 'Indique si se muestra el encabezado de valores de referencia.',
            'mostrarReferencias.in' => 'El valor debe ser Sí o No.',
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
        $data['mostrarReferencias'] = (int) $data['mostrarReferencias'];

        if (! Grupo::tieneColumnaMostrarReferencias()) {
            $mensaje = 'No se puede guardar el grupo: falta la columna grupos.mostrarReferencias. '
                .'Ejecute el SQL de database/sql/grupos_mostrar_referencias.sql.';
            $this->dispatch('vl-swal-error', mensaje: $mensaje);
            throw ValidationException::withMessages(['mostrarReferencias' => $mensaje]);
        }

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
