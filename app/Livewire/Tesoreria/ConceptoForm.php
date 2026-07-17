<?php

namespace App\Livewire\Tesoreria;

use App\Models\Concepto;
use App\Models\TipoMovimiento;
use App\Support\PermisosIaCatalog;
use App\Support\Tesoreria\TesoreriaConfig;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Alta/edición de conceptos (variante tesoreria_movimientos / labvetciudad).
 */
class ConceptoForm extends Component
{
    public ?int $idConcepto = null;

    public ?int $tipoConcepto = null;

    public string $concepto = '';

    public ?int $orden = null;

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaMovimientos(), 404);
        abort_unless(Schema::hasTable('conceptos'), 404);

        if ($id) {
            $reg = Concepto::query()->findOrFail($id);
            $this->idConcepto = (int) $reg->id;
            $this->tipoConcepto = $reg->tipoConcepto ? (int) $reg->tipoConcepto : null;
            $this->concepto = (string) ($reg->concepto ?? '');
            $this->orden = $reg->orden !== null ? (int) $reg->orden : null;
        }
    }

    public function rules(): array
    {
        return [
            'tipoConcepto' => ['required', 'integer', Rule::exists('tipomovimiento', 'id')],
            'concepto' => [
                'required',
                'string',
                'max:100',
                Rule::unique('conceptos', 'concepto')
                    ->where(fn ($q) => $q->where('tipoConcepto', $this->tipoConcepto))
                    ->ignore($this->idConcepto, 'id'),
            ],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipoConcepto.required' => 'El tipo de movimiento es obligatorio.',
            'tipoConcepto.exists' => 'El tipo de movimiento seleccionado no es válido.',
            'concepto.required' => 'El nombre del concepto es obligatorio.',
            'concepto.max' => 'El nombre no puede superar 100 caracteres.',
            'concepto.unique' => 'Ya existe un concepto con ese nombre para el tipo seleccionado.',
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden no puede ser negativo.',
            'orden.max' => 'El orden no puede superar 9999.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaMovimientos(), 404);

        $key = 'concepto-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $data = $this->validate();
        $data['concepto'] = trim($data['concepto']);
        $data['tipoConcepto'] = (int) $data['tipoConcepto'];
        $data['orden'] = isset($data['orden']) ? (int) $data['orden'] : 0;

        if ($this->idConcepto) {
            $reg = Concepto::query()->findOrFail($this->idConcepto);
            $reg->update($data);
            $mensaje = 'Concepto actualizado correctamente.';
        } else {
            Concepto::query()->create($data);
            $mensaje = 'Concepto creado correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('tesoreria.conceptos.index', navigate: false);
    }

    public function render()
    {
        $titulo = $this->idConcepto ? 'Editar concepto' : 'Nuevo concepto';
        $tipos = Schema::hasTable('tipomovimiento')
            ? TipoMovimiento::query()->orderBy('tipoMovimiento')->get(['id', 'tipoMovimiento'])
            : collect();

        return view('livewire.tesoreria.concepto-form', compact('titulo', 'tipos'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
