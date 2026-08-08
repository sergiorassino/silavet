<?php

namespace App\Livewire\Tesoreria;

use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\MedioDePago;
use App\Models\Movimiento;
use App\Models\Paciente;
use App\Models\Proveedor;
use App\Models\TipoMovimiento;
use App\Support\PermisosIaCatalog;
use App\Support\Tesoreria\MovimientosCajaConsulta;
use App\Support\Tesoreria\TesoreriaConfig;
use App\Support\UsuarioMenuPortal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tesorería sobre tabla `movimientos` (variante tesoreria_pacientes / labvetciudad).
 */
class MovimientosCajaIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public bool $formAbierto = false;

    public bool $asientoAbierto = false;

    public ?int $idMovimiento = null;

    public string $fechaElegirProtocolos = '';

    public ?int $idTipoMovimiento = null;

    public ?int $idCuentas = null;

    public ?int $idConcepto = null;

    public ?int $idProveedores = null;

    public ?int $idPacientes = null;

    /** Protocolo elegido en el selector de Cadetería (se guarda en idPacientes). */
    public ?int $idCadete = null;

    public string $monto = '0.00';

    public string $comprobante = '';

    public string $obs = '';

    public string $fecha = '';

    public string $hora = '';

    public string $busqueda = '';

    /** Filtro de listado: Y-m-d (por defecto hoy). */
    public string $fechaDesde = '';

    public string $fechaHasta = '';

    /** Modal Asiento (transferencia entre cuentas → 2 filas en movimientos). */
    public string $asientoFecha = '';

    public string $asientoHora = '';

    public ?int $asientoIdCuentaOrigen = null;

    public ?int $asientoIdCuentaDestino = null;

    public ?int $asientoIdClientes = null;

    public string $asientoMonto = '';

    public string $asientoObs = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaPacientes(), 404);
        abort_unless(Schema::hasTable('movimientos'), 404);

        $this->fechaElegirProtocolos = now()->toDateString();
        $hoy = now()->toDateString();
        $this->fechaDesde = $hoy;
        $this->fechaHasta = $hoy;
        $this->reiniciarFechaHora();
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage();
    }

    public function getExcelUrlProperty(): string
    {
        [$desde, $hasta] = $this->rangoFechasFiltro();

        return route('tesoreria.movimientos.excel', array_filter([
            'busqueda' => trim($this->busqueda) !== '' ? trim($this->busqueda) : null,
            'desde' => $desde,
            'hasta' => $hasta,
        ]));
    }

    public function updatedIdTipoMovimiento(): void
    {
        $this->idConcepto = null;
        $this->idProveedores = null;
        $this->idPacientes = null;
        $this->idCadete = null;
        if (! $this->esEgreso()) {
            $this->idProveedores = null;
        }
    }

    public function updatedIdConcepto(): void
    {
        $this->idProveedores = null;
        $this->idPacientes = null;
        $this->idCadete = null;
    }

    public function updatedFechaElegirProtocolos(): void
    {
        $this->idPacientes = null;
        $this->idCadete = null;

        // La fecha elegida filtra protocolos y también es la del movimiento (fechhora).
        if ($this->fechaElegirProtocolos !== ''
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->fechaElegirProtocolos) === 1) {
            $this->fecha = $this->fechaElegirProtocolos;
        }
    }

    public function updatedIdPacientes(): void
    {
        if (! TesoreriaConfig::esConceptoIngresosDiarios($this->idConcepto) || ! $this->idPacientes) {
            return;
        }

        $paciente = Paciente::query()
            ->whereKey((int) $this->idPacientes)
            ->first(['idPacientes', 'precio', 'idClientes']);

        if ($paciente === null) {
            return;
        }

        $this->monto = number_format((float) $paciente->precio, 2, '.', '');
    }

    public function updatedIdCadete(): void
    {
        if (! TesoreriaConfig::esConceptoCadeteria($this->idConcepto) || ! $this->idCadete) {
            return;
        }

        $paciente = Paciente::query()
            ->whereKey((int) $this->idCadete)
            ->first(['idPacientes', 'cadete', 'idClientes']);

        if ($paciente === null) {
            return;
        }

        $this->monto = number_format((float) $paciente->cadete, 2, '.', '');
        $this->idPacientes = (int) $paciente->idPacientes;
    }

    public function abrirFormularioNuevo(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $this->cancelarAsiento();
        $this->resetFormulario();
        $this->idTipoMovimiento = TipoMovimiento::INGRESO;
        $this->fechaElegirProtocolos = now()->toDateString();
        $this->reiniciarFechaHora();
        $this->formAbierto = true;
        $this->resetErrorBag();
    }

    public function abrirFormularioEditar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $this->cancelarAsiento();

        $mov = $this->movimientoEnAlcance($id);
        if ($mov === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el movimiento.');

            return;
        }

        $this->idMovimiento = (int) $mov->id;
        $this->idTipoMovimiento = (int) ($mov->idTipoMovimiento ?: 0) ?: null;
        $this->idCuentas = (int) ($mov->idCuentas ?: 0) ?: null;
        $this->idConcepto = (int) ($mov->idConcepto ?: 0) ?: null;
        $this->idProveedores = (int) ($mov->idProveedores ?: 0) ?: null;
        $this->idPacientes = (int) ($mov->idPacientes ?: 0) ?: null;
        $this->idCadete = TesoreriaConfig::esConceptoCadeteria($this->idConcepto)
            ? $this->idPacientes
            : null;
        $this->monto = number_format(abs((float) $mov->monto), 2, '.', '');
        $this->comprobante = (string) ($mov->comprobante ?? '');
        $this->obs = (string) ($mov->obs ?? '');
        $this->fecha = $mov->fechhora?->format('Y-m-d') ?? now()->toDateString();
        $this->hora = $mov->fechhora?->format('H:i:s') ?? now()->format('H:i:s');
        $this->fechaElegirProtocolos = $mov->paciente?->fechhoy?->toDateString()
            ?? now()->toDateString();
        $this->formAbierto = true;
        $this->resetErrorBag();
    }

    public function cancelarFormulario(): void
    {
        $this->formAbierto = false;
        $this->resetFormulario();
        $this->resetErrorBag();
    }

    public function abrirFormularioAsiento(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $this->cancelarFormulario();
        $this->resetAsiento();
        $this->asientoAbierto = true;
        $this->resetErrorBag();
    }

    public function cancelarAsiento(): void
    {
        $this->asientoAbierto = false;
        $this->resetAsiento();
        $this->resetErrorBag();
    }

    public function guardarAsiento(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'tesoreria-movimientos-asiento:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $this->asientoMonto = $this->normalizarImporte($this->asientoMonto);
        $this->asientoHora = $this->normalizarHora($this->asientoHora);

        $this->validate([
            'asientoFecha' => ['required', 'date_format:Y-m-d'],
            'asientoHora' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'asientoIdCuentaOrigen' => ['required', 'integer', Rule::exists('mediodepago', 'id')],
            'asientoIdCuentaDestino' => [
                'required',
                'integer',
                Rule::exists('mediodepago', 'id'),
                'different:asientoIdCuentaOrigen',
            ],
            'asientoIdClientes' => ['required', 'integer', Rule::exists('clientes', 'idClientes')],
            'asientoMonto' => ['required', 'numeric', 'gt:0'],
            'asientoObs' => ['nullable', 'string', 'max:65535'],
        ], [
            'asientoFecha.required' => 'Ingrese la fecha.',
            'asientoFecha.date_format' => 'La fecha no es válida.',
            'asientoHora.required' => 'Ingrese la hora.',
            'asientoIdCuentaOrigen.required' => 'Seleccione la cuenta de origen.',
            'asientoIdCuentaOrigen.exists' => 'La cuenta de origen no es válida.',
            'asientoIdCuentaDestino.required' => 'Seleccione la cuenta de destino.',
            'asientoIdCuentaDestino.exists' => 'La cuenta de destino no es válida.',
            'asientoIdCuentaDestino.different' => 'La cuenta de destino debe ser distinta a la de origen.',
            'asientoIdClientes.required' => 'Seleccione el cliente.',
            'asientoIdClientes.exists' => 'El cliente seleccionado no es válido.',
            'asientoMonto.required' => 'Ingrese el monto.',
            'asientoMonto.numeric' => 'El monto no es válido.',
            'asientoMonto.gt' => 'El monto debe ser mayor a cero.',
        ]);

        RateLimiter::hit($key, 60);

        $importe = abs(round((float) $this->asientoMonto, 2));
        $fechhora = $this->asientoFecha.' '.$this->asientoHora;
        $obs = trim($this->asientoObs);
        $idClientes = (int) $this->asientoIdClientes;

        $base = [
            'idClientes' => $idClientes,
            'idPacientes' => 0,
            'idConcepto' => 0,
            'idProveedores' => 0,
            'fechhora' => $fechhora,
            'comprobante' => '',
            'obs' => $obs !== '' ? $obs : null,
            'fechaCheque' => null,
            'numCheque' => '',
        ];

        DB::transaction(function () use ($base, $importe) {
            // Retiro de la cuenta de origen (egreso, monto negativo).
            Movimiento::create(array_merge($base, [
                'idCuentas' => (int) $this->asientoIdCuentaOrigen,
                'idTipoMovimiento' => TipoMovimiento::EGRESO,
                'monto' => $importe * -1,
            ]));

            // Depósito en la cuenta de destino (ingreso, monto positivo).
            Movimiento::create(array_merge($base, [
                'idCuentas' => (int) $this->asientoIdCuentaDestino,
                'idTipoMovimiento' => TipoMovimiento::INGRESO,
                'monto' => $importe,
            ]));
        });

        $this->cancelarAsiento();
        $this->resetPage();
        $this->dispatch('vl-swal-exito', mensaje: 'Asiento registrado correctamente.');
    }

    public function guardar(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'tesoreria-movimientos-caja:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $this->monto = $this->normalizarImporte($this->monto);
        $this->hora = $this->normalizarHora($this->hora);

        $esEgreso = $this->esEgreso();
        $esIngresosDiarios = ! $esEgreso && TesoreriaConfig::esConceptoIngresosDiarios($this->idConcepto);
        $esCadeteria = ! $esEgreso && TesoreriaConfig::esConceptoCadeteria($this->idConcepto);

        if ($esCadeteria && $this->idCadete) {
            $this->idPacientes = (int) $this->idCadete;
        }

        $reglas = [
            'idTipoMovimiento' => [
                'required',
                'integer',
                Rule::exists('tipomovimiento', 'id'),
            ],
            'idCuentas' => ['required', 'integer', Rule::exists('mediodepago', 'id')],
            'idConcepto' => [
                'required',
                'integer',
                Rule::exists('conceptos', 'id')->where(
                    fn ($q) => $q->where('tipoConcepto', (int) $this->idTipoMovimiento)
                ),
            ],
            'monto' => ['required', 'numeric', 'gt:0'],
            'comprobante' => ['nullable', 'string', 'max:20'],
            'obs' => ['nullable', 'string', 'max:65535'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'fechaElegirProtocolos' => ['nullable', 'date_format:Y-m-d'],
        ];

        if ($esEgreso) {
            $reglas['idProveedores'] = [
                'nullable',
                'integer',
                Rule::exists('proveedores', 'id')->where(
                    fn ($q) => $q->where('idConceptos', (int) $this->idConcepto)
                ),
            ];
        }

        if ($esIngresosDiarios) {
            $reglas['idPacientes'] = ['required', 'integer', 'exists:pacientes,idPacientes'];
        }

        if ($esCadeteria) {
            $reglas['idCadete'] = ['required', 'integer', 'exists:pacientes,idPacientes'];
        }

        $this->validate($reglas, [
            'idTipoMovimiento.required' => 'Seleccione el tipo de movimiento.',
            'idTipoMovimiento.exists' => 'El tipo de movimiento no es válido.',
            'idCuentas.required' => 'Seleccione la cuenta.',
            'idCuentas.exists' => 'La cuenta seleccionada no es válida.',
            'idConcepto.required' => 'Seleccione el concepto.',
            'idConcepto.exists' => 'El concepto seleccionado no es válido.',
            'idProveedores.exists' => 'El proveedor seleccionado no es válido.',
            'idPacientes.required' => 'Debe seleccionar un protocolo de pacientes.',
            'idPacientes.exists' => 'El protocolo seleccionado no es válido.',
            'idCadete.required' => 'Debe seleccionar un protocolo de cadetería.',
            'idCadete.exists' => 'El protocolo de cadetería no es válido.',
            'monto.required' => 'Ingrese el monto.',
            'monto.numeric' => 'El monto no es válido.',
            'monto.gt' => 'El monto debe ser mayor a cero.',
            'fecha.required' => 'Ingrese la fecha del registro.',
            'hora.required' => 'Ingrese la hora del registro.',
        ]);

        RateLimiter::hit($key, 60);

        $montoAbs = abs(round((float) $this->monto, 2));
        $montoFinal = $esEgreso ? ($montoAbs * -1) : $montoAbs;

        $idClientes = 0;
        $idPacientes = 0;

        if ($esIngresosDiarios || $esCadeteria) {
            $idProtocolo = $esCadeteria ? (int) $this->idCadete : (int) $this->idPacientes;
            $paciente = Paciente::query()->whereKey($idProtocolo)->first(['idPacientes', 'idClientes', 'precio', 'cadete']);
            if ($paciente === null) {
                $this->dispatch('vl-swal-error', mensaje: 'No se encontró el protocolo seleccionado.');

                return;
            }
            $idPacientes = (int) $paciente->idPacientes;
            $idClientes = (int) ($paciente->idClientes ?? 0);
            if ($esCadeteria) {
                $montoFinal = abs(round((float) $paciente->cadete, 2));
            } else {
                $montoFinal = abs(round((float) $paciente->precio, 2));
            }
        }

        $payload = [
            'idCuentas' => (int) $this->idCuentas,
            'idTipoMovimiento' => (int) $this->idTipoMovimiento,
            'idClientes' => $idClientes,
            'idPacientes' => $idPacientes,
            'idConcepto' => (int) $this->idConcepto,
            'idProveedores' => $esEgreso ? (int) ($this->idProveedores ?? 0) : 0,
            'fechhora' => $this->fecha.' '.$this->hora,
            'comprobante' => trim($this->comprobante),
            'monto' => $montoFinal,
            'obs' => trim($this->obs) !== '' ? trim($this->obs) : null,
            'fechaCheque' => null,
            'numCheque' => '',
        ];

        if ($this->idMovimiento !== null) {
            $mov = $this->movimientoEnAlcance($this->idMovimiento);
            if ($mov === null) {
                $this->dispatch('vl-swal-error', mensaje: 'No se encontró el movimiento.');
                $this->cancelarFormulario();

                return;
            }
        }

        DB::transaction(function () use ($payload, $esIngresosDiarios, $esCadeteria, $idPacientes) {
            if ($this->idMovimiento !== null) {
                Movimiento::query()->whereKey($this->idMovimiento)->update($payload);
            } else {
                Movimiento::create($payload);
            }

            if ($idPacientes > 0 && Schema::hasColumn('pacientes', 'cargado')) {
                if ($esIngresosDiarios) {
                    Paciente::query()->whereKey($idPacientes)->update(['cargado' => '✅']);
                } elseif ($esCadeteria && Schema::hasColumn('pacientes', 'cargadoCadete')) {
                    Paciente::query()->whereKey($idPacientes)->update(['cargadoCadete' => '✅']);
                }
            }
        });

        $mensaje = $this->idMovimiento !== null
            ? 'Movimiento actualizado correctamente.'
            : 'Movimiento registrado correctamente.';

        $this->cancelarFormulario();
        $this->resetPage();
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);
    }

    public function eliminar(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        if ($this->idMovimiento === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No hay movimiento seleccionado.');

            return;
        }

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'tesoreria-movimientos-caja-del:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);

        $mov = $this->movimientoEnAlcance($this->idMovimiento);
        if ($mov === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el movimiento.');
            $this->cancelarFormulario();

            return;
        }

        RateLimiter::hit($key, 60);

        $idPacientes = (int) ($mov->idPacientes ?? 0);
        $idConcepto = (int) ($mov->idConcepto ?? 0);

        DB::transaction(function () use ($mov, $idPacientes, $idConcepto) {
            $mov->delete();

            if ($idPacientes <= 0 || $idConcepto <= 0) {
                return;
            }

            $quedan = Movimiento::query()
                ->where('idPacientes', $idPacientes)
                ->where('idConcepto', $idConcepto)
                ->exists();

            if ($quedan) {
                return;
            }

            if (TesoreriaConfig::esConceptoIngresosDiarios($idConcepto)
                && Schema::hasColumn('pacientes', 'cargado')) {
                Paciente::query()->whereKey($idPacientes)->update(['cargado' => '']);
            } elseif (TesoreriaConfig::esConceptoCadeteria($idConcepto)
                && Schema::hasColumn('pacientes', 'cargadoCadete')) {
                Paciente::query()->whereKey($idPacientes)->update(['cargadoCadete' => '']);
            }
        });

        $this->cancelarFormulario();
        $this->resetPage();
        $this->dispatch('vl-swal-exito', mensaje: 'Movimiento eliminado correctamente.');
    }

    public function render()
    {
        [$desde, $hasta] = $this->rangoFechasFiltro();

        $movimientos = MovimientosCajaConsulta::listado($this->busqueda, $desde, $hasta)
            ->paginate(self::POR_PAGINA);

        $tipos = Schema::hasTable('tipomovimiento')
            ? TipoMovimiento::query()->orderBy('tipoMovimiento')->get(['id', 'tipoMovimiento'])
            : collect();

        $cuentas = Schema::hasTable('mediodepago')
            ? MedioDePago::query()->orderBy('orden')->orderBy('nombreMedioPago')->get(['id', 'nombreMedioPago'])
            : collect();

        $conceptos = ($this->idTipoMovimiento && Schema::hasTable('conceptos'))
            ? Concepto::query()
                ->where('tipoConcepto', (int) $this->idTipoMovimiento)
                ->orderBy('orden')
                ->orderBy('concepto')
                ->get(['id', 'concepto'])
            : collect();

        $proveedores = ($this->esEgreso() && $this->idConcepto && Schema::hasTable('proveedores'))
            ? Proveedor::query()
                ->where('idConceptos', (int) $this->idConcepto)
                ->orderBy('proveedor')
                ->get(['id', 'proveedor'])
            : collect();

        $fechasProtocolos = $this->opcionesFechaProtocolos();
        $protocolosIngresos = $this->protocolosIngresosDiarios();
        $protocolosCadeteria = $this->protocolosCadeteria();

        $clientes = ($this->asientoAbierto && Schema::hasTable('clientes'))
            ? Cliente::query()->orderBy('nombre')->get(['idClientes', 'nombre'])
            : collect();

        return view('livewire.tesoreria.movimientos-caja-index', [
            'movimientos' => $movimientos,
            'tipos' => $tipos,
            'cuentas' => $cuentas,
            'conceptos' => $conceptos,
            'proveedores' => $proveedores,
            'fechasProtocolos' => $fechasProtocolos,
            'protocolosIngresos' => $protocolosIngresos,
            'protocolosCadeteria' => $protocolosCadeteria,
            'clientes' => $clientes,
            'mostrarPaciente' => TesoreriaConfig::esConceptoIngresosDiarios($this->idConcepto),
            'mostrarCadeteria' => TesoreriaConfig::esConceptoCadeteria($this->idConcepto),
            'esEgreso' => $this->esEgreso(),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function esEgreso(): bool
    {
        return (int) $this->idTipoMovimiento === TipoMovimiento::EGRESO;
    }

    /**
     * @return array{0: string, 1: string} [desde, hasta] Y-m-d; si se invierten, se intercambian.
     */
    private function rangoFechasFiltro(): array
    {
        $hoy = now()->toDateString();
        $desde = $this->fechaFiltroNormalizada($this->fechaDesde) ?? $hoy;
        $hasta = $this->fechaFiltroNormalizada($this->fechaHasta) ?? $hoy;

        if ($desde > $hasta) {
            return [$hasta, $desde];
        }

        return [$desde, $hasta];
    }

    private function fechaFiltroNormalizada(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) !== 1) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $valor)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function movimientoEnAlcance(int $id): ?Movimiento
    {
        return Movimiento::query()->whereKey($id)->first();
    }

    /** @return Collection<int, array{valor: string, etiqueta: string}> */
    private function opcionesFechaProtocolos(): Collection
    {
        $dias = TesoreriaConfig::diasProtocolos();
        $items = collect();

        for ($i = 0; $i < $dias; $i++) {
            $fecha = Carbon::today()->subDays($i);
            $items->push([
                'valor' => $fecha->toDateString(),
                'etiqueta' => $fecha->format('d/m/Y'),
            ]);
        }

        return $items;
    }

    /** @return Collection<int, object{idPacientes: int, etiqueta: string}> */
    private function protocolosIngresosDiarios(): Collection
    {
        if (! TesoreriaConfig::esConceptoIngresosDiarios($this->idConcepto)
            || $this->fechaElegirProtocolos === ''
            || ! Schema::hasTable('pacientes')) {
            return collect();
        }

        $idConcepto = TesoreriaConfig::idConceptoIngresosDiarios();

        $marcaSql = $idConcepto !== null
            ? 'CASE WHEN EXISTS (
                    SELECT 1 FROM movimientos m
                    WHERE m.idPacientes = pacientes.idPacientes
                      AND m.idConcepto = '.(int) $idConcepto.'
               ) THEN \'✅\' ELSE COALESCE(NULLIF(pacientes.cargado, \'\'), \'❌\') END'
            : 'COALESCE(NULLIF(pacientes.cargado, \'\'), \'❌\')';

        return Paciente::query()
            ->from('pacientes')
            ->join('clientes', 'pacientes.idClientes', '=', 'clientes.idClientes')
            ->whereDate('pacientes.fechhoy', $this->fechaElegirProtocolos)
            // Mismo orden que el módulo Pacientes (Paciente::scopeOrdenListado).
            ->ordenListado()
            ->select([
                'pacientes.idPacientes',
                DB::raw("CONCAT(
                    {$marcaSql},
                    ' - ', pacientes.fechhoy,
                    ' - Cliente: ', clientes.nombre,
                    ' - Protocolo: ', pacientes.nombreProtocolo,
                    ' - Paciente: ', pacientes.nombre,
                    ' - Importe: ', pacientes.precio
                ) AS etiqueta"),
            ])
            ->get();
    }

    /** @return Collection<int, object{idPacientes: int, etiqueta: string}> */
    private function protocolosCadeteria(): Collection
    {
        if (! TesoreriaConfig::esConceptoCadeteria($this->idConcepto)
            || $this->fechaElegirProtocolos === ''
            || ! Schema::hasTable('pacientes')) {
            return collect();
        }

        $idConcepto = TesoreriaConfig::idConceptoCadeteria();

        $marcaSql = $idConcepto !== null
            ? 'CASE WHEN EXISTS (
                    SELECT 1 FROM movimientos m
                    WHERE m.idPacientes = pacientes.idPacientes
                      AND m.idConcepto = '.(int) $idConcepto.'
               ) THEN \'✅\' ELSE COALESCE(NULLIF(pacientes.cargadoCadete, \'\'), \'❌\') END'
            : 'COALESCE(NULLIF(pacientes.cargadoCadete, \'\'), \'❌\')';

        return Paciente::query()
            ->from('pacientes')
            ->join('clientes', 'pacientes.idClientes', '=', 'clientes.idClientes')
            ->whereDate('pacientes.fechhoy', $this->fechaElegirProtocolos)
            ->where('pacientes.cadete', '>', 0)
            // Mismo orden que el módulo Pacientes (Paciente::scopeOrdenListado).
            ->ordenListado()
            ->select([
                'pacientes.idPacientes',
                DB::raw("CONCAT(
                    {$marcaSql},
                    ' - ', pacientes.fechhoy,
                    ' - Cliente: ', clientes.nombre,
                    ' - Protocolo: ', pacientes.nombreProtocolo,
                    ' - Paciente: ', pacientes.nombre,
                    ' - Cadetería: ', pacientes.cadete
                ) AS etiqueta"),
            ])
            ->get();
    }

    private function resetFormulario(): void
    {
        $this->idMovimiento = null;
        $this->idTipoMovimiento = TipoMovimiento::INGRESO;
        $this->idCuentas = null;
        $this->idConcepto = null;
        $this->idProveedores = null;
        $this->idPacientes = null;
        $this->idCadete = null;
        $this->monto = '0.00';
        $this->comprobante = '';
        $this->obs = '';
        $this->fechaElegirProtocolos = now()->toDateString();
        $this->reiniciarFechaHora();
    }

    private function resetAsiento(): void
    {
        $this->asientoIdCuentaOrigen = null;
        $this->asientoIdCuentaDestino = null;
        $this->asientoIdClientes = null;
        $this->asientoMonto = '';
        $this->asientoObs = '';
        $ahora = now();
        $this->asientoFecha = $ahora->toDateString();
        $this->asientoHora = $ahora->format('H:i:s');
    }

    private function reiniciarFechaHora(): void
    {
        $ahora = now();
        $this->fecha = $ahora->toDateString();
        $this->hora = $ahora->format('H:i:s');
    }

    private function normalizarHora(string $valor): string
    {
        $valor = trim($valor);
        if (preg_match('/^\d{2}:\d{2}$/', $valor)) {
            return $valor.':00';
        }

        return $valor;
    }

    private function normalizarImporte(string $valor): string
    {
        $valor = trim(str_replace(' ', '', $valor));
        if ($valor === '') {
            return $valor;
        }

        if (str_contains($valor, ',') && str_contains($valor, '.')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } elseif (str_contains($valor, ',')) {
            $valor = str_replace(',', '.', $valor);
        }

        return $valor;
    }
}
