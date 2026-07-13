<?php

namespace App\Livewire\Abm\Tipodeterminaciones;

use App\Models\Derivacion;
use App\Models\Tipodeterminacion;
use App\Support\PermisosIaCatalog;
use App\Support\PrecioInput;
use App\Support\Tipodeterminaciones\TipodeterminacionesGridConfig;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class TipodeterminacionIndex extends Component
{
    public string $busqueda = '';

    /** @var array<int, array<string, mixed>> */
    public array $filas = [];

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::DETERMINACIONES), 403);
        $this->sincronizarFilasDesdeBd();
    }

    public function updatedBusqueda(): void
    {
        //
    }

    public function guardarFila(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::DETERMINACIONES), 403);

        $key = 'tipodet-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $fila = $this->filas[$id] ?? null;
        if ($fila === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró la fila a guardar.');

            return;
        }

        $validated = validator($fila, $this->reglasFila(), [
            'orden.required' => 'El orden es obligatorio.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar 50 caracteres.',
            'destino.exists' => 'El centro de derivación seleccionado no es válido.',
        ])->validate();

        $registro = Tipodeterminacion::query()->findOrFail($id);

        $data = [
            'orden' => (int) $validated['orden'],
            'nombre' => trim($validated['nombre']),
            'precio' => PrecioInput::parse($validated['precio']),
            'destino' => $this->destinoParaGuardar($validated['destino']),
        ];

        if (TipodeterminacionesGridConfig::mostrarColumnaPerfil()) {
            $data['perfil'] = (int) $validated['perfil'];
        }

        if ($this->tieneColumnaPrecioExtra()) {
            $data['precio2'] = PrecioInput::parse((string) ($validated['precio2'] ?? '0'));
            $data['precio3'] = PrecioInput::parse((string) ($validated['precio3'] ?? '0'));
        }

        $registro->update($data);
        $this->filas[$id] = $this->filaDesdeModelo($registro->fresh());

        $this->dispatch('vl-swal-exito', mensaje: 'Determinación guardada correctamente.');
    }

    public function descartarFila(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::DETERMINACIONES), 403);

        $registro = Tipodeterminacion::query()->findOrFail($id);
        $this->filas[$id] = $this->filaDesdeModelo($registro);
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::DETERMINACIONES), 403);

        $key = 'tipodet-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 60);

        if (Schema::hasTable('determinaciones')) {
            $enUso = DB::table('determinaciones')
                ->where('idTipodeterminaciones', $id)
                ->exists();

            if ($enUso) {
                $this->dispatch(
                    'vl-swal-error',
                    mensaje: 'No se puede eliminar: la determinación está asociada a protocolos.',
                    titulo: 'Determinación en uso'
                );

                return;
            }
        }

        Tipodeterminacion::query()->whereKey($id)->delete();
        unset($this->filas[$id]);

        $this->dispatch('vl-swal-exito', mensaje: 'Determinación eliminada.');
    }

    public function agregarFila(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::DETERMINACIONES), 403);

        $key = 'tipodet-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $maxOrden = (int) Tipodeterminacion::query()->max('orden');

        $data = [
            'orden' => $maxOrden + 1,
            'nombre' => 'Nueva determinación',
            'precio' => 0,
            'filaDesde' => 0,
            'filasCant' => 0,
            'destino' => 0,
            'perfil' => 0,
        ];

        if ($this->tieneColumnaPrecioExtra()) {
            $data['precio2'] = 0;
            $data['precio3'] = 0;
        }

        $nuevo = Tipodeterminacion::query()->create($data);
        $this->filas[$nuevo->idTipodeterminaciones] = $this->filaDesdeModelo($nuevo);

        $this->dispatch('vl-swal-exito', mensaje: 'Fila nueva agregada. Edite los datos y guarde.');
    }

    public function render()
    {
        $term = trim(mb_strtolower($this->busqueda));
        $tienePrecioExtra = $this->tieneColumnaPrecioExtra();

        $idsVisibles = collect($this->filas)
            ->filter(function (array $fila) use ($term) {
                if ($term === '') {
                    return true;
                }

                return str_contains(mb_strtolower((string) $fila['nombre']), $term)
                    || str_contains((string) $fila['orden'], $term);
            })
            ->sortBy([
                fn (array $fila) => (int) $fila['orden'],
                fn (array $fila) => mb_strtolower((string) $fila['nombre']),
            ])
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return view('livewire.abm.tipodeterminaciones.tipodeterminacion-index', [
            'idsVisibles' => $idsVisibles,
            'tienePrecioExtra' => $tienePrecioExtra,
            'mostrarColumnaPerfil' => TipodeterminacionesGridConfig::mostrarColumnaPerfil(),
            'derivacionEsCatalogo' => TipodeterminacionesGridConfig::derivacionEsCatalogo(),
            'centrosDerivacion' => $this->centrosDerivacion(),
            'columnasVisibles' => TipodeterminacionesGridConfig::columnasVisibles($tienePrecioExtra),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    /** @return array<string, mixed> */
    private function reglasFila(): array
    {
        $rules = [
            'orden' => ['required', 'integer', 'min:0', 'max:9999'],
            'nombre' => ['required', 'string', 'max:50'],
            'precio' => ['required', 'string'],
            'precio2' => ['nullable', 'string'],
            'precio3' => ['nullable', 'string'],
        ];

        if (TipodeterminacionesGridConfig::mostrarColumnaPerfil()) {
            $rules['perfil'] = ['required', 'in:0,1'];
        }

        if (TipodeterminacionesGridConfig::derivacionEsCatalogo()) {
            $rules['destino'] = [
                'required',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ((int) $value === 0) {
                        return;
                    }

                    if (! Derivacion::query()->whereKey((int) $value)->exists()) {
                        $fail('El centro de derivación seleccionado no es válido.');
                    }
                },
            ];
        } else {
            $rules['destino'] = ['required', 'in:0,1'];
        }

        return $rules;
    }

    private function destinoParaGuardar(mixed $valor): int
    {
        if (TipodeterminacionesGridConfig::derivacionEsCatalogo()) {
            return (int) $valor;
        }

        return (int) $valor > 0 ? 1 : 0;
    }

    private function sincronizarFilasDesdeBd(): void
    {
        $this->filas = Tipodeterminacion::query()
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->mapWithKeys(fn (Tipodeterminacion $registro) => [
                (int) $registro->idTipodeterminaciones => $this->filaDesdeModelo($registro),
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    private function filaDesdeModelo(Tipodeterminacion $registro): array
    {
        $fila = [
            'orden' => (string) $registro->orden,
            'nombre' => (string) $registro->nombre,
            'precio' => PrecioInput::format($registro->precio),
            'destino' => $this->destinoParaFormulario((int) $registro->destino),
        ];

        if (TipodeterminacionesGridConfig::mostrarColumnaPerfil()) {
            $fila['perfil'] = (int) $registro->perfil > 0 ? '1' : '0';
        }

        if ($this->tieneColumnaPrecioExtra()) {
            $fila['precio2'] = PrecioInput::format($registro->precio2 ?? 0);
            $fila['precio3'] = PrecioInput::format($registro->precio3 ?? 0);
        }

        return $fila;
    }

    private function destinoParaFormulario(int $destino): string
    {
        if (TipodeterminacionesGridConfig::derivacionEsCatalogo()) {
            return (string) $destino;
        }

        return $destino > 0 ? '1' : '0';
    }

    private function tieneColumnaPrecioExtra(): bool
    {
        return Schema::hasColumn('tipodeterminaciones', 'precio2')
            && Schema::hasColumn('tipodeterminaciones', 'precio3');
    }

    /** @return \Illuminate\Support\Collection<int, Derivacion> */
    private function centrosDerivacion()
    {
        if (! TipodeterminacionesGridConfig::derivacionEsCatalogo()
            || ! Schema::hasTable('derivaciones')) {
            return collect();
        }

        return Derivacion::query()
            ->orderBy('derivacion')
            ->get();
    }
}
