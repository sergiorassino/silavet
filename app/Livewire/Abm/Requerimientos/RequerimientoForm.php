<?php

namespace App\Livewire\Abm\Requerimientos;

use App\Models\Requerimiento;
use App\Support\PermisosIaCatalog;
use App\Support\Requerimientos\RequerimientoHtml;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;

class RequerimientoForm extends Component
{
    public const HTML_MAX = 20000;

    public ?int $idRequerimiento = null;

    public string $titulo = '';

    public string $requerimiento = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        if ($id) {
            $registro = Requerimiento::findOrFail($id);
            $this->idRequerimiento = (int) $registro->id;
            $this->titulo = (string) $registro->titulo;
            $this->requerimiento = (string) $registro->requerimiento;
        }
    }

    public function rules(): array
    {
        return [
            'titulo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('requerimientos', 'titulo')->ignore($this->idRequerimiento, 'id'),
            ],
            'requerimiento' => [
                'required',
                'string',
                'max:'.self::HTML_MAX,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título del procedimiento es obligatorio.',
            'titulo.max' => 'El título no puede superar 30 caracteres.',
            'titulo.unique' => 'Ya existe un procedimiento con ese título.',
            'requerimiento.required' => 'Describa el modo de toma de muestra.',
            'requerimiento.max' => 'El texto HTML no puede superar '.self::HTML_MAX.' caracteres.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'requerimiento-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $this->requerimiento = RequerimientoHtml::sanitizar($this->requerimiento);

        $data = $this->validate();
        $data['titulo'] = trim($data['titulo']);
        $data['requerimiento'] = RequerimientoHtml::sanitizar(trim($data['requerimiento']));

        if ($data['requerimiento'] === '') {
            $this->addError('requerimiento', 'Describa el modo de toma de muestra.');

            return;
        }

        if ($this->idRequerimiento) {
            $registro = Requerimiento::findOrFail($this->idRequerimiento);
            $registro->update($data);
            $mensaje = 'Procedimiento actualizado correctamente.';
        } else {
            Requerimiento::create($data);
            $mensaje = 'Procedimiento creado correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('abm.requerimientos.index', navigate: false);
    }

    public function render()
    {
        $tituloPagina = $this->idRequerimiento ? 'Editar procedimiento' : 'Nuevo procedimiento';

        return view('livewire.abm.requerimientos.requerimiento-form', [
            'tituloPagina' => $tituloPagina,
            'htmlMax' => self::HTML_MAX,
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
