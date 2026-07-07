<?php

namespace App\Livewire\Protocolos;

use App\Models\Derivacion;
use App\Models\Determinacion;
use App\Models\Paciente;
use App\Models\Tipodeterminacion;
use App\Support\PermisosIaCatalog;
use App\Support\PrecioInput;
use App\Support\Precios\PrecioDeterminacionResolver;
use App\Support\Tipodeterminaciones\TipodeterminacionesGridConfig;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class PacienteDeterminaciones extends Component
{
    public int $idPacientes;

    public string $busquedaRapida = '';

    /** @var array<int, array<string, mixed>> */
    public array $filas = [];

    /** @var array<string, mixed>|null */
    public ?array $filaNueva = null;

    private ?Paciente $pacienteCache = null;

    public function mount(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);
        abort_unless(Schema::hasTable('determinaciones'), 404, 'La tabla de determinaciones no está disponible.');

        $this->pacienteCache = $this->pacienteEnAlcance($id);
        $this->idPacientes = $this->pacienteCache->idPacientes;
        $this->sincronizarFilasDesdeBd();
    }

    public function updatedBusquedaRapida(): void
    {
        //
    }

    public function agregarDeterminacion(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        if ($this->filaNueva !== null) {
            return;
        }

        $this->filaNueva = [
            'idTipodeterminaciones' => '',
            'precio' => '',
            'descuento' => '',
            'idDerivaciones' => '0',
        ];
        $this->busquedaRapida = '';
    }

    public function updatedFilaNuevaIdTipodeterminaciones(mixed $value): void
    {
        if ($this->filaNueva === null || $value === '' || $value === null) {
            return;
        }

        $this->aplicarPrecioYDescuentoDesdeTipo((int) $value, $this->filaNueva);
    }

    public function updatedFilas(mixed $value, string $key): void
    {
        if (! str_contains($key, '.')) {
            return;
        }

        [$id, $campo] = explode('.', $key, 2);

        if ($campo !== 'precio') {
            return;
        }

        $idInt = (int) $id;
        if (! isset($this->filas[$idInt])) {
            return;
        }

        $precio = PrecioInput::parse((string) ($this->filas[$idInt]['precio'] ?? '0'));
        $this->filas[$idInt]['descuento'] = PrecioInput::format(
            PrecioDeterminacionResolver::calcularDescuento($precio, $this->porcentajeDescuentoCliente())
        );
    }

    public function confirmarNueva(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        if ($this->filaNueva === null) {
            return;
        }

        $key = 'prot-det-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 40), 429);
        RateLimiter::hit($key, 60);

        $validated = validator($this->filaNueva, $this->reglasFila(), $this->mensajesValidacion())->validate();

        $idTipo = (int) $validated['idTipodeterminaciones'];

        if ($this->tipoYaCargado($idTipo)) {
            $this->dispatch('vl-swal-error', mensaje: 'Esa determinación ya está cargada en este protocolo.');

            return;
        }

        $paciente = $this->paciente();

        Determinacion::query()->create([
            'idClientes' => $paciente->idClientes,
            'idPacientes' => $paciente->idPacientes,
            'idTipodeterminaciones' => $idTipo,
            'precio' => PrecioInput::parse($validated['precio']),
            'descuento' => PrecioInput::parse($validated['descuento']),
            'idDerivaciones' => $this->derivacionParaGuardar($validated['idDerivaciones']),
        ]);

        $this->filaNueva = null;
        $this->sincronizarFilasDesdeBd();
        $this->actualizarTotalProtocolo();

        $this->dispatch('vl-swal-exito', mensaje: 'Determinación agregada.');
    }

    public function cancelarNueva(): void
    {
        $this->filaNueva = null;
        $this->busquedaRapida = '';
    }

    public function guardarFila(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $key = 'prot-det-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 40), 429);
        RateLimiter::hit($key, 60);

        $fila = $this->filas[$id] ?? null;
        if ($fila === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró la fila a guardar.');

            return;
        }

        $validated = validator($fila, $this->reglasFilaEdicion(), $this->mensajesValidacion())->validate();

        $registro = Determinacion::query()
            ->where('idPacientes', $this->idPacientes)
            ->whereKey($id)
            ->firstOrFail();

        $registro->update([
            'precio' => PrecioInput::parse($validated['precio']),
            'descuento' => PrecioInput::parse($validated['descuento']),
            'idDerivaciones' => $this->derivacionParaGuardar($validated['idDerivaciones']),
        ]);

        $this->filas[$id] = $this->filaDesdeModelo($registro->fresh(['tipodeterminacion']));
        $this->actualizarTotalProtocolo();
    }

    public function guardarDerivacion(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $fila = $this->filas[$id] ?? null;
        if ($fila === null) {
            return;
        }

        $validated = validator(
            ['idDerivaciones' => $fila['idDerivaciones'] ?? '0'],
            ['idDerivaciones' => $this->reglaDerivacion()],
            $this->mensajesValidacion()
        )->validate();

        Determinacion::query()
            ->where('idPacientes', $this->idPacientes)
            ->whereKey($id)
            ->update([
                'idDerivaciones' => $this->derivacionParaGuardar($validated['idDerivaciones']),
            ]);

        $registro = Determinacion::query()->with('tipodeterminacion')->findOrFail($id);
        $this->filas[$id] = $this->filaDesdeModelo($registro);
    }

    public function eliminar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $key = 'prot-det-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 20), 429);
        RateLimiter::hit($key, 60);

        Determinacion::query()
            ->where('idPacientes', $this->idPacientes)
            ->whereKey($id)
            ->delete();

        unset($this->filas[$id]);
        $this->actualizarTotalProtocolo();

        $this->dispatch('vl-swal-exito', mensaje: 'Determinación eliminada.');
    }

    public function render()
    {
        $paciente = $this->paciente()->load(['cliente']);
        $term = trim(mb_strtolower($this->busquedaRapida));

        $tiposDisponibles = Tipodeterminacion::query()
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->filter(function (Tipodeterminacion $tipo) use ($term) {
                if ($term === '') {
                    return true;
                }

                return str_contains(mb_strtolower($tipo->nombre), $term);
            })
            ->values();

        $idsCargados = collect($this->filas)->pluck('idTipodeterminaciones')->map(fn ($v) => (int) $v)->all();

        return view('livewire.protocolos.paciente-determinaciones', [
            'paciente' => $paciente,
            'tiposDisponibles' => $tiposDisponibles,
            'idsCargados' => $idsCargados,
            'derivacionEsCatalogo' => TipodeterminacionesGridConfig::derivacionEsCatalogo(),
            'centrosDerivacion' => $this->centrosDerivacion(),
            'totalProtocolo' => PrecioInput::format($this->totalNeto()),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function paciente(): Paciente
    {
        if ($this->pacienteCache === null) {
            $this->pacienteCache = $this->pacienteEnAlcance($this->idPacientes);
        }

        return $this->pacienteCache->loadMissing('cliente');
    }

    private function pacienteEnAlcance(int $id): Paciente
    {
        $ctx = labCtx();

        return Paciente::query()
            ->when($ctx->esCliente() && $ctx->idClientes, fn ($q) => $q->where('idClientes', $ctx->idClientes))
            ->where('idPacientes', $id)
            ->firstOrFail();
    }

    private function sincronizarFilasDesdeBd(): void
    {
        $this->filas = Determinacion::query()
            ->with('tipodeterminacion')
            ->where('idPacientes', $this->idPacientes)
            ->orderBy('idDeterminaciones')
            ->get()
            ->mapWithKeys(fn (Determinacion $registro) => [
                (int) $registro->idDeterminaciones => $this->filaDesdeModelo($registro),
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    private function filaDesdeModelo(Determinacion $registro): array
    {
        return [
            'idTipodeterminaciones' => (int) $registro->idTipodeterminaciones,
            'nombre' => (string) ($registro->tipodeterminacion?->nombre ?? '—'),
            'precio' => PrecioInput::format($registro->precio),
            'descuento' => PrecioInput::format($registro->descuento),
            'idDerivaciones' => $this->derivacionParaFormulario((int) $registro->idDerivaciones),
        ];
    }

    private function aplicarPrecioYDescuentoDesdeTipo(int $idTipo, array &$fila): void
    {
        $tipo = Tipodeterminacion::query()->find($idTipo);
        if ($tipo === null) {
            return;
        }

        $paciente = $this->paciente();
        $precio = PrecioDeterminacionResolver::resolverPrecioLista1((int) $paciente->idClientes, $tipo);
        $descuento = PrecioDeterminacionResolver::calcularDescuento($precio, $this->porcentajeDescuentoCliente());

        $fila['precio'] = PrecioInput::format($precio);
        $fila['descuento'] = PrecioInput::format($descuento);
        $fila['idDerivaciones'] = $this->derivacionParaFormulario((int) $tipo->destino);
    }

    private function porcentajeDescuentoCliente(): float
    {
        return (float) ($this->paciente()->cliente?->descuento ?? 0);
    }

    private function tipoYaCargado(int $idTipo): bool
    {
        foreach ($this->filas as $fila) {
            if ((int) ($fila['idTipodeterminaciones'] ?? 0) === $idTipo) {
                return true;
            }
        }

        return false;
    }

    private function actualizarTotalProtocolo(): void
    {
        $total = Determinacion::query()
            ->where('idPacientes', $this->idPacientes)
            ->get(['precio', 'descuento'])
            ->sum(fn (Determinacion $d) => PrecioDeterminacionResolver::neto((float) $d->precio, (float) $d->descuento));

        Paciente::query()
            ->whereKey($this->idPacientes)
            ->update(['precio' => round($total, 2)]);

        if ($this->pacienteCache !== null) {
            $this->pacienteCache->precio = round($total, 2);
        }
    }

    private function totalNeto(): float
    {
        return collect($this->filas)->sum(function (array $fila) {
            return PrecioDeterminacionResolver::neto(
                PrecioInput::parse((string) ($fila['precio'] ?? '0')),
                PrecioInput::parse((string) ($fila['descuento'] ?? '0'))
            );
        });
    }

    /** @return array<string, mixed> */
    private function reglasFila(): array
    {
        return [
            'idTipodeterminaciones' => ['required', 'integer', 'exists:tipodeterminaciones,idTipodeterminaciones'],
            'precio' => ['required', 'string'],
            'descuento' => ['required', 'string'],
            'idDerivaciones' => $this->reglaDerivacion(),
        ];
    }

    /** @return array<string, mixed> */
    private function reglasFilaEdicion(): array
    {
        return [
            'precio' => ['required', 'string'],
            'descuento' => ['required', 'string'],
            'idDerivaciones' => $this->reglaDerivacion(),
        ];
    }

    /** @return array<int, mixed> */
    private function reglaDerivacion(): array
    {
        if (TipodeterminacionesGridConfig::derivacionEsCatalogo()) {
            return [
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
        }

        return ['required', 'in:0,1'];
    }

    /** @return array<string, string> */
    private function mensajesValidacion(): array
    {
        return [
            'idTipodeterminaciones.required' => 'Seleccione una determinación.',
            'idTipodeterminaciones.exists' => 'La determinación seleccionada no es válida.',
            'precio.required' => 'El precio es obligatorio.',
            'descuento.required' => 'El descuento es obligatorio.',
        ];
    }

    private function derivacionParaGuardar(mixed $valor): int
    {
        if (TipodeterminacionesGridConfig::derivacionEsCatalogo()) {
            return (int) $valor;
        }

        return (int) $valor > 0 ? 1 : 0;
    }

    private function derivacionParaFormulario(int $idDerivaciones): string
    {
        if (TipodeterminacionesGridConfig::derivacionEsCatalogo()) {
            return (string) $idDerivaciones;
        }

        return $idDerivaciones > 0 ? '1' : '0';
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
