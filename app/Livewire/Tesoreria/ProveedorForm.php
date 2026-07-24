<?php

namespace App\Livewire\Tesoreria;

use App\Models\Concepto;
use App\Models\Proveedor;
use App\Support\CuitInput;
use App\Support\PermisosIaCatalog;
use App\Support\Tesoreria\TesoreriaConfig;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Alta/edición de proveedores (variante tesoreria_pacientes / labvetciudad).
 */
class ProveedorForm extends Component
{
    public ?int $idProveedor = null;

    public ?int $idConceptos = null;

    public string $proveedor = '';

    public string $cuit = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaPacientes(), 404);
        abort_unless(Schema::hasTable('proveedores'), 404);

        if ($id) {
            $reg = Proveedor::query()->findOrFail($id);
            $this->idProveedor = (int) $reg->id;
            $this->idConceptos = $reg->idConceptos ? (int) $reg->idConceptos : null;
            $this->proveedor = (string) ($reg->proveedor ?? '');
            $this->cuit = CuitInput::format((string) ($reg->cuit ?? ''));
        }
    }

    public function updatedCuit(string $value): void
    {
        $this->resetErrorBag('cuit');
        $this->cuit = CuitInput::format($value);
    }

    public function rules(): array
    {
        return [
            'idConceptos' => ['required', 'integer', Rule::exists('conceptos', 'id')],
            'proveedor' => [
                'required',
                'string',
                'max:200',
                Rule::unique('proveedores', 'proveedor')
                    ->where(fn ($q) => $q->where('idConceptos', $this->idConceptos))
                    ->ignore($this->idProveedor, 'id'),
            ],
            'cuit' => [
                'nullable',
                'string',
                'max:'.CuitInput::FORMATTED_LENGTH,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $digits = CuitInput::normalize((string) $value);
                    if ($digits !== '' && strlen($digits) !== CuitInput::DIGITS_LENGTH) {
                        $fail('El CUIT debe tener 11 dígitos (formato 99-99999999-9).');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'idConceptos.required' => 'El concepto es obligatorio.',
            'idConceptos.exists' => 'El concepto seleccionado no es válido.',
            'proveedor.required' => 'El nombre del proveedor es obligatorio.',
            'proveedor.max' => 'El nombre no puede superar 200 caracteres.',
            'proveedor.unique' => 'Ya existe un proveedor con ese nombre para el concepto seleccionado.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaPacientes(), 404);

        $key = 'proveedor-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $data = $this->validate();
        $data['proveedor'] = trim($data['proveedor']);
        $data['idConceptos'] = (int) $data['idConceptos'];
        $cuit = CuitInput::normalize(trim((string) ($data['cuit'] ?? '')));
        $data['cuit'] = $cuit !== '' ? $cuit : null;

        if ($this->idProveedor) {
            $reg = Proveedor::query()->findOrFail($this->idProveedor);
            $reg->update($data);
            $mensaje = 'Proveedor actualizado correctamente.';
        } else {
            Proveedor::query()->create($data);
            $mensaje = 'Proveedor creado correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('tesoreria.proveedores.index', navigate: false);
    }

    public function render()
    {
        $titulo = $this->idProveedor ? 'Editar proveedor' : 'Nuevo proveedor';
        $conceptos = Schema::hasTable('conceptos')
            ? Concepto::query()->orderBy('orden')->orderBy('concepto')->get(['id', 'concepto'])
            : collect();

        return view('livewire.tesoreria.proveedor-form', compact('titulo', 'conceptos'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
