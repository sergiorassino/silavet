<?php

namespace App\Livewire\Abm\Rangovalores;

use App\Models\Especie;
use App\Models\Itemsinforme;
use App\Models\Rangovalor;
use App\Support\PermisosIaCatalog;
use App\Support\SexoCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class RangovalorIndex extends Component
{
    // Panel izquierdo: especie seleccionada
    public string $busquedaEspecie = '';

    public ?int $idEspeciesSeleccionada = null;

    // Formulario alta/edición en bloque
    public string $formIdItems = '';

    /** @var list<int> */
    public array $formSexos = [];

    public string $formValorMin = '';

    public string $formValorMax = '';

    public bool $formVisible = false;

    /** @var array<int, array<string, mixed>> */
    public array $filas = [];

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);
        abort_unless(Schema::hasTable('rangovalores'), 404, 'La tabla de rangos no está disponible.');

        $primera = Especie::query()->orderBy('nombre')->value('idEspecies');
        if ($primera !== null) {
            $this->seleccionarEspecie((int) $primera);
        }
    }

    public function seleccionarEspecie(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        Especie::query()->findOrFail($id);
        $this->idEspeciesSeleccionada = $id;
        $this->formVisible = false;
        $this->limpiarForm();
        $this->sincronizarFilas();
    }

    public function updatedBusquedaEspecie(): void
    {
        $this->idEspeciesSeleccionada = null;
        $this->filas = [];
        $this->limpiarForm();
    }

    public function abrirForm(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        if ($this->idEspeciesSeleccionada === null) {
            $this->dispatch('vl-swal-error', mensaje: 'Seleccione una especie primero.');

            return;
        }

        $this->limpiarForm();
        // Pre-marcar todos los sexos por defecto al abrir el formulario.
        $this->formSexos = array_map(
            static fn (array $s): int => $s['id'],
            SexoCatalog::opcionesConId()
        );
        $this->formVisible = true;
    }

    public function cerrarForm(): void
    {
        $this->formVisible = false;
        $this->limpiarForm();
    }

    /** Cuando cambia el ítem del form, precarga los sexos y valores que ya existen. */
    public function updatedFormIdItems(): void
    {
        $this->cargarSexosExistentes();
    }

    public function seleccionarTodosLosSexos(): void
    {
        $this->formSexos = array_map(
            static fn (array $s): int => $s['id'],
            SexoCatalog::opcionesConId()
        );
    }

    public function deseleccionarTodosLosSexos(): void
    {
        $this->formSexos = [];
    }

    public function guardarForm(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'rangovalor-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $idsSexosValidos = array_map(
            static fn (array $s): int => $s['id'],
            SexoCatalog::opcionesConId()
        );

        $validated = $this->validate([
            'formIdItems' => ['required', 'integer', 'exists:itemsinforme,idItems'],
            'formSexos' => ['required', 'array', 'min:1'],
            'formSexos.*' => ['required', 'integer', 'in:'.implode(',', $idsSexosValidos)],
            'formValorMin' => ['required', 'numeric', 'min:0'],
            'formValorMax' => ['required', 'numeric', 'min:0', 'gte:formValorMin'],
        ], [
            'formIdItems.required' => 'Seleccione un ítem del informe.',
            'formIdItems.exists' => 'El ítem seleccionado no es válido.',
            'formSexos.required' => 'Seleccione al menos un sexo.',
            'formSexos.min' => 'Seleccione al menos un sexo.',
            'formValorMin.required' => 'Ingrese el valor mínimo.',
            'formValorMin.numeric' => 'El valor mínimo debe ser numérico.',
            'formValorMax.required' => 'Ingrese el valor máximo.',
            'formValorMax.numeric' => 'El valor máximo debe ser numérico.',
            'formValorMax.gte' => 'El valor máximo debe ser mayor o igual al mínimo.',
        ]);

        $idItems = (int) $validated['formIdItems'];
        $idEspecies = (int) $this->idEspeciesSeleccionada;
        $sexosMarcados = array_map('intval', (array) $validated['formSexos']);
        $valorMin = (float) str_replace(',', '.', (string) $validated['formValorMin']);
        $valorMax = (float) str_replace(',', '.', (string) $validated['formValorMax']);

        foreach ($sexosMarcados as $idSexos) {
            Rangovalor::query()->updateOrCreate(
                ['idItems' => $idItems, 'idEspecies' => $idEspecies, 'idSexos' => $idSexos],
                ['valorMin' => $valorMin, 'valorMax' => $valorMax]
            );
        }

        // Borrar sexos desmarcados para la misma combinación ítem+especie.
        Rangovalor::query()
            ->where('idItems', $idItems)
            ->where('idEspecies', $idEspecies)
            ->whereNotIn('idSexos', $sexosMarcados)
            ->delete();

        $this->sincronizarFilas();
        $this->limpiarForm();
        $this->formVisible = false;
        $this->dispatch('vl-swal-exito', mensaje: 'Valores de referencia guardados correctamente.');
    }

    public function guardarFila(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'rangovalor-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $fila = $this->filas[$id] ?? null;
        if ($fila === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el registro.');

            return;
        }

        $minRaw = str_replace(',', '.', trim((string) ($fila['valorMin'] ?? '')));
        $maxRaw = str_replace(',', '.', trim((string) ($fila['valorMax'] ?? '')));

        $validated = validator(
            ['valorMin' => $minRaw !== '' ? $minRaw : null, 'valorMax' => $maxRaw !== '' ? $maxRaw : null],
            [
                'valorMin' => ['nullable', 'numeric', 'min:0'],
                'valorMax' => ['nullable', 'numeric', 'min:0'],
            ],
            [
                'valorMin.numeric' => 'El mínimo debe ser numérico.',
                'valorMax.numeric' => 'El máximo debe ser numérico.',
            ]
        )->validate();

        $registro = $this->filaDeEspecieSeleccionada($id);
        $registro->update([
            'valorMin' => $validated['valorMin'] !== null ? (float) $validated['valorMin'] : null,
            'valorMax' => $validated['valorMax'] !== null ? (float) $validated['valorMax'] : null,
        ]);

        $this->filas[$id] = $this->filaDesdeModelo($registro->fresh(['itemsinforme']));
        $this->dispatch('vl-swal-exito', mensaje: 'Registro guardado.');
    }

    public function descartarFila(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $registro = $this->filaDeEspecieSeleccionada($id);
        $this->filas[$id] = $this->filaDesdeModelo($registro->fresh(['itemsinforme']));
    }

    public function eliminarFila(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'rangovalor-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        $this->filaDeEspecieSeleccionada($id)->delete();
        unset($this->filas[$id]);
        $this->dispatch('vl-swal-exito', mensaje: 'Registro eliminado.');
    }

    public function eliminarItemDeEspecie(int $idItems): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'rangovalor-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        if ($this->idEspeciesSeleccionada === null) {
            return;
        }

        $eliminados = Rangovalor::query()
            ->where('idEspecies', $this->idEspeciesSeleccionada)
            ->where('idItems', $idItems)
            ->delete();

        // Quitar del array en memoria
        foreach ($this->filas as $id => $fila) {
            if ((int) $fila['idItems'] === $idItems) {
                unset($this->filas[$id]);
            }
        }

        $this->dispatch('vl-swal-exito', mensaje: "Se eliminaron {$eliminados} rango(s) del ítem.");
    }

    public function eliminarTodosDeEspecie(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'rangovalor-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        if ($this->idEspeciesSeleccionada === null) {
            return;
        }

        Rangovalor::query()
            ->where('idEspecies', $this->idEspeciesSeleccionada)
            ->delete();

        $this->filas = [];
        $this->dispatch('vl-swal-exito', mensaje: 'Todos los rangos de esta especie fueron eliminados.');
    }

    public function guardarTodos(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'rangovalor-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $omitidos = 0;

        foreach ($this->filas as $id => $fila) {
            $minRaw = str_replace(',', '.', trim((string) ($fila['valorMin'] ?? '')));
            $maxRaw = str_replace(',', '.', trim((string) ($fila['valorMax'] ?? '')));

            if (($minRaw !== '' && ! is_numeric($minRaw)) || ($maxRaw !== '' && ! is_numeric($maxRaw))) {
                $omitidos++;

                continue;
            }

            $registro = Rangovalor::query()
                ->where('idEspecies', $this->idEspeciesSeleccionada)
                ->find($id);

            if ($registro === null) {
                continue;
            }

            $registro->update([
                'valorMin' => $minRaw !== '' ? (float) $minRaw : null,
                'valorMax' => $maxRaw !== '' ? (float) $maxRaw : null,
            ]);
        }

        $this->sincronizarFilas();

        if ($omitidos > 0) {
            $this->dispatch('vl-swal-error', mensaje: "Se guardaron los registros válidos. {$omitidos} registro(s) con valores no numéricos fueron omitidos.");
        } else {
            $this->dispatch('vl-swal-exito', mensaje: 'Todos los registros guardados.');
        }
    }

    public function render()
    {
        $termEspecie = trim(mb_strtolower($this->busquedaEspecie));

        $conteosPorEspecie = Rangovalor::query()
            ->selectRaw('idEspecies, COUNT(*) as total')
            ->groupBy('idEspecies')
            ->pluck('total', 'idEspecies');

        $especies = Especie::query()
            ->when($termEspecie !== '', fn ($q) => $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$termEspecie}%"]))
            ->orderBy('nombre')
            ->get(['idEspecies', 'nombre']);

        $especieActiva = $this->idEspeciesSeleccionada !== null
            ? Especie::query()->find($this->idEspeciesSeleccionada)
            : null;

        $items = Itemsinforme::query()
            ->orderBy('nombreItem')
            ->get(['idItems', 'nombreItem']);

        return view('livewire.abm.rangovalores.rangovalor-index', [
            'especies' => $especies,
            'conteosPorEspecie' => $conteosPorEspecie,
            'especieActiva' => $especieActiva,
            'items' => $items,
            'sexosDisponibles' => SexoCatalog::opcionesConId(),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    // ─── Privados ───────────────────────────────────────────────────────────

    private function filaDeEspecieSeleccionada(int $id): Rangovalor
    {
        return Rangovalor::query()
            ->where('idEspecies', $this->idEspeciesSeleccionada)
            ->findOrFail($id);
    }

    private function sincronizarFilas(): void
    {
        if ($this->idEspeciesSeleccionada === null) {
            $this->filas = [];

            return;
        }

        $registros = Rangovalor::query()
            ->with('itemsinforme')
            ->where('idEspecies', $this->idEspeciesSeleccionada)
            ->get()
            ->sortBy(fn ($r) => mb_strtolower($r->itemsinforme?->nombreItem ?? '') . '|' . str_pad((string) $r->idSexos, 5, '0', STR_PAD_LEFT));

        $this->filas = [];
        foreach ($registros as $r) {
            $this->filas[(int) $r->idRangovalores] = $this->filaDesdeModelo($r);
        }
    }

    /** @return array<string, mixed> */
    private function filaDesdeModelo(Rangovalor $r): array
    {
        return [
            'idRangovalores' => (int) $r->idRangovalores,
            'idItems' => (int) $r->idItems,
            'idEspecies' => (int) $r->idEspecies,
            'idSexos' => (int) $r->idSexos,
            'valorMin' => $r->valorMin !== null ? (string) $r->valorMin : '',
            'valorMax' => $r->valorMax !== null ? (string) $r->valorMax : '',
            'nombreItem' => (string) ($r->itemsinforme?->nombreItem ?? '—'),
            'nombreSexo' => SexoCatalog::nombrePorId((int) $r->idSexos),
        ];
    }

    private function cargarSexosExistentes(): void
    {
        $this->formSexos = [];
        $this->formValorMin = '';
        $this->formValorMax = '';

        if ($this->formIdItems === '' || $this->idEspeciesSeleccionada === null) {
            return;
        }

        $existentes = Rangovalor::query()
            ->where('idItems', (int) $this->formIdItems)
            ->where('idEspecies', $this->idEspeciesSeleccionada)
            ->orderBy('idSexos')
            ->get();

        if ($existentes->isEmpty()) {
            return;
        }

        $this->formSexos = $existentes->pluck('idSexos')->map(fn ($v): int => (int) $v)->all();

        $primera = $existentes->first();
        $this->formValorMin = $primera->valorMin !== null ? (string) $primera->valorMin : '';
        $this->formValorMax = $primera->valorMax !== null ? (string) $primera->valorMax : '';
    }

    private function limpiarForm(): void
    {
        $this->formIdItems = '';
        $this->formSexos = [];
        $this->formValorMin = '';
        $this->formValorMax = '';
        $this->resetValidation();
    }
}
