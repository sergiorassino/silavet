<?php

namespace App\Livewire\Protocolos;

use App\Models\Derivacion;
use App\Models\Determinacion;
use App\Models\Paciente;
use App\Models\Tipodeterminacion;
use App\Support\PermisosIaCatalog;
use App\Support\PrecioInput;
use App\Support\Precios\PrecioDeterminacionResolver;
use App\Support\Resultados\RenglonesMaterializer;
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
            $this->dispatch('vl-prot-det-focus-tipo');

            return;
        }

        $this->filaNueva = [
            'idTipodeterminaciones' => '',
            'neto' => '',
            'descuento' => '',
            'precio' => '',
            'idDerivaciones' => '0',
        ];
        $this->busquedaRapida = '';
        $this->dispatch('vl-prot-det-focus-tipo');
    }

    public function updatedFilaNuevaIdTipodeterminaciones(mixed $value): void
    {
        if ($this->filaNueva === null || $value === '' || $value === null) {
            return;
        }

        $this->aplicarPrecioYDescuentoDesdeTipo((int) $value, $this->filaNueva);
        // El morph de Livewire saca el foco del <select>; lo devolvemos para poder confirmar con Enter.
        $this->dispatch('vl-prot-det-focus-tipo');
    }

    public function updatedFilaNuevaNeto(mixed $value): void
    {
        if ($this->filaNueva === null) {
            return;
        }

        $this->recalcularDescuentoDesdePorcentaje($this->filaNueva);
        $this->recalcularPrecioConDescuento($this->filaNueva);
    }

    public function updatedFilaNuevaDescuento(mixed $value): void
    {
        if ($this->filaNueva === null) {
            return;
        }

        $this->recalcularPrecioConDescuento($this->filaNueva);
    }

    public function updatedFilas(mixed $value, string $key): void
    {
        if (! str_contains($key, '.')) {
            return;
        }

        [$id, $campo] = explode('.', $key, 2);
        $idInt = (int) $id;
        if (! isset($this->filas[$idInt])) {
            return;
        }

        if ($campo === 'neto') {
            $this->recalcularDescuentoDesdePorcentaje($this->filas[$idInt]);
            $this->recalcularPrecioConDescuento($this->filas[$idInt]);

            return;
        }

        if ($campo === 'descuento') {
            $this->recalcularPrecioConDescuento($this->filas[$idInt]);
        }
    }

    public function confirmarNueva(mixed $idTipodeterminaciones = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        if ($this->filaNueva === null) {
            return;
        }

        $key = 'prot-det-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 40), 429);
        RateLimiter::hit($key, 60);

        if ($idTipodeterminaciones !== null && $idTipodeterminaciones !== '') {
            $this->filaNueva['idTipodeterminaciones'] = (string) $idTipodeterminaciones;
        }

        $idTipoElegido = (int) ($this->filaNueva['idTipodeterminaciones'] ?? 0);
        $netoActual = PrecioInput::parse((string) ($this->filaNueva['neto'] ?? ''));
        if ($idTipoElegido > 0 && $netoActual <= 0) {
            $this->aplicarPrecioYDescuentoDesdeTipo($idTipoElegido, $this->filaNueva);
        } else {
            $this->recalcularPrecioConDescuento($this->filaNueva);
        }

        $validated = validator($this->filaNueva, $this->reglasFila(), $this->mensajesValidacion())->validate();

        $idTipo = (int) $validated['idTipodeterminaciones'];

        if ($this->tipoYaCargado($idTipo)) {
            $this->dispatch('vl-swal-error', mensaje: 'Esa determinación ya está cargada en este protocolo.');

            return;
        }

        $paciente = $this->paciente();
        $neto = PrecioInput::parse($validated['neto']);
        $descuento = PrecioInput::parse($validated['descuento']);
        $precio = PrecioDeterminacionResolver::precioConDescuento($neto, $descuento);

        $payload = [
            'idClientes' => $paciente->idClientes,
            'idPacientes' => $paciente->idPacientes,
            'idTipodeterminaciones' => $idTipo,
            'precio' => $precio,
            'descuento' => $descuento,
            'idDerivaciones' => $this->derivacionParaGuardar($validated['idDerivaciones']),
        ];

        if ($this->tieneColumnaDeterminacionesNeto()) {
            $payload['neto'] = $neto;
        }

        Determinacion::query()->create($payload);

        (new RenglonesMaterializer)->asegurarParaDeterminacion(
            $paciente,
            $idTipo,
            (int) $paciente->idClientes
        );

        $this->filaNueva = null;
        $this->sincronizarFilasDesdeBd();
        $this->actualizarTotalProtocolo();
        // Deja lista otra fila nueva para cargar en serie con teclado.
        $this->agregarDeterminacion();
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

        $this->recalcularPrecioConDescuento($fila);
        $this->filas[$id] = $fila;

        $validated = validator($fila, $this->reglasFilaEdicion(), $this->mensajesValidacion())->validate();

        $registro = Determinacion::query()
            ->where('idPacientes', $this->idPacientes)
            ->whereKey($id)
            ->firstOrFail();

        $neto = PrecioInput::parse($validated['neto']);
        $descuento = PrecioInput::parse($validated['descuento']);
        $precio = PrecioDeterminacionResolver::precioConDescuento($neto, $descuento);

        $payload = [
            'precio' => $precio,
            'descuento' => $descuento,
            'idDerivaciones' => $this->derivacionParaGuardar($validated['idDerivaciones'] ?? $fila['idDerivaciones']),
        ];

        if ($this->tieneColumnaDeterminacionesNeto()) {
            $payload['neto'] = $neto;
        }

        $registro->update($payload);

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

        $registro = Determinacion::query()
            ->where('idPacientes', $this->idPacientes)
            ->whereKey($id)
            ->first();

        if ($registro === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró la determinación.');

            return;
        }

        $idTipo = (int) $registro->idTipodeterminaciones;

        $registro->delete();

        (new RenglonesMaterializer)->eliminarParaDeterminacion($this->idPacientes, $idTipo);

        unset($this->filas[$id]);
        $this->actualizarTotalProtocolo();
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
            'totalProtocolo' => PrecioInput::format($this->totalPrecioConDescuento()),
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
        $descuento = (float) ($registro->descuento ?? 0);
        $neto = $this->tieneColumnaDeterminacionesNeto()
            ? (float) ($registro->neto ?? 0)
            : 0.0;
        $precio = (float) ($registro->precio ?? 0);

        // Datos legacy: precio era lista y neto aún no estaba cargado.
        if ($neto <= 0 && $precio > 0) {
            $neto = $precio;
            $precio = PrecioDeterminacionResolver::precioConDescuento($neto, $descuento);
        } elseif ($precio <= 0 && $neto > 0) {
            $precio = PrecioDeterminacionResolver::precioConDescuento($neto, $descuento);
        }

        return [
            'idTipodeterminaciones' => (int) $registro->idTipodeterminaciones,
            'nombre' => (string) ($registro->tipodeterminacion?->nombre ?? '—'),
            'neto' => PrecioInput::format($neto),
            'descuento' => PrecioInput::format($descuento),
            'precio' => PrecioInput::format($precio),
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
        $neto = PrecioDeterminacionResolver::resolverPrecioLista1((int) $paciente->idClientes, $tipo);
        $descuento = PrecioDeterminacionResolver::calcularDescuento($neto, $this->porcentajeDescuentoCliente());
        $precio = PrecioDeterminacionResolver::precioConDescuento($neto, $descuento);

        $fila['neto'] = PrecioInput::format($neto);
        $fila['descuento'] = PrecioInput::format($descuento);
        $fila['precio'] = PrecioInput::format($precio);
        $fila['idDerivaciones'] = $this->derivacionParaFormulario((int) $tipo->destino);
    }

    /** @param array<string, mixed> $fila */
    private function recalcularDescuentoDesdePorcentaje(array &$fila): void
    {
        $neto = PrecioInput::parse((string) ($fila['neto'] ?? '0'));
        $fila['descuento'] = PrecioInput::format(
            PrecioDeterminacionResolver::calcularDescuento($neto, $this->porcentajeDescuentoCliente())
        );
    }

    /** @param array<string, mixed> $fila */
    private function recalcularPrecioConDescuento(array &$fila): void
    {
        $neto = PrecioInput::parse((string) ($fila['neto'] ?? '0'));
        $descuento = PrecioInput::parse((string) ($fila['descuento'] ?? '0'));
        $fila['precio'] = PrecioInput::format(
            PrecioDeterminacionResolver::precioConDescuento($neto, $descuento)
        );
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
        $columnas = ['precio', 'descuento'];
        if ($this->tieneColumnaDeterminacionesNeto()) {
            $columnas[] = 'neto';
        }

        $lineas = Determinacion::query()
            ->where('idPacientes', $this->idPacientes)
            ->get($columnas);

        $totalPrecio = 0.0;
        $totalNeto = 0.0;

        foreach ($lineas as $linea) {
            $descuento = (float) ($linea->descuento ?? 0);
            $neto = $this->tieneColumnaDeterminacionesNeto()
                ? (float) ($linea->neto ?? 0)
                : 0.0;
            $precio = (float) ($linea->precio ?? 0);

            if ($neto <= 0 && $precio > 0) {
                $neto = $precio;
                $precio = PrecioDeterminacionResolver::precioConDescuento($neto, $descuento);
            } elseif ($precio <= 0) {
                $precio = PrecioDeterminacionResolver::precioConDescuento($neto, $descuento);
            }

            $totalNeto += $neto;
            $totalPrecio += $precio;
        }

        $totalNeto = round($totalNeto, 2);
        $totalPrecio = round($totalPrecio, 2);

        $payload = [
            'precio' => $totalPrecio,
        ];

        if ($this->tieneColumnaPacientesNeto()) {
            $payload['neto'] = $totalNeto;
        }

        Paciente::query()
            ->whereKey($this->idPacientes)
            ->update($payload);

        if ($this->pacienteCache !== null) {
            $this->pacienteCache->precio = $totalPrecio;
            if ($this->tieneColumnaPacientesNeto()) {
                $this->pacienteCache->neto = $totalNeto;
            }
        }
    }

    private function totalPrecioConDescuento(): float
    {
        return collect($this->filas)->sum(function (array $fila) {
            return PrecioInput::parse((string) ($fila['precio'] ?? '0'));
        });
    }

    private function tieneColumnaDeterminacionesNeto(): bool
    {
        return Schema::hasColumn('determinaciones', 'neto');
    }

    private function tieneColumnaPacientesNeto(): bool
    {
        return Schema::hasColumn('pacientes', 'neto');
    }

    /** @return array<string, mixed> */
    private function reglasFila(): array
    {
        return [
            'idTipodeterminaciones' => ['required', 'integer', 'exists:tipodeterminaciones,idTipodeterminaciones'],
            'neto' => ['required', 'string'],
            'descuento' => ['required', 'string'],
            'idDerivaciones' => $this->reglaDerivacion(),
        ];
    }

    /** @return array<string, mixed> */
    private function reglasFilaEdicion(): array
    {
        return [
            'neto' => ['required', 'string'],
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
            'neto.required' => 'El neto es obligatorio.',
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
