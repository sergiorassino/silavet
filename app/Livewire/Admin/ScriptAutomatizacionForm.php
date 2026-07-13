<?php

namespace App\Livewire\Admin;

use App\Models\Entorno;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class ScriptAutomatizacionForm extends Component
{
    public string $formulas = '';

    private function normalizarCodigo(string $codigo): string
    {
        $codigo = (string) $codigo;

        // Si viene pegado como HTML, extrae el contenido del primer <script>...</script>.
        if (preg_match('~<script\b[^>]*>([\s\S]*?)</script>~i', $codigo, $m) === 1) {
            return trim((string) ($m[1] ?? ''));
        }

        return $codigo;
    }

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $entorno = Entorno::query()->orderBy('id')->first();
        abort_if($entorno === null, 404);

        $this->formulas = (string) ($entorno->formulas ?? '');
    }

    public function rules(): array
    {
        return [
            'formulas' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'formulas.string' => 'El script debe ser texto.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'entorno-formulas-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 20), 429);

        $data = $this->validate();
        $codigo = $this->normalizarCodigo((string) ($data['formulas'] ?? ''));

        $entorno = Entorno::query()->orderBy('id')->first();
        abort_if($entorno === null, 404);

        $entorno->update([
            'formulas' => $codigo,
        ]);

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: 'Script actualizado correctamente.');
    }

    public function render()
    {
        return view('livewire.admin.script-automatizacion-form')
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
