<?php

namespace App\Livewire\Tesoreria;

use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\CuentaDetalle;
use App\Models\MedioDePago;
use App\Models\Paciente;
use App\Support\Facturacion\FacturacionAfipConfig;
use App\Support\Facturacion\FacturacionAfipIndicadores;
use App\Support\PermisosIaCatalog;
use App\Support\Security\OpaqueRouteToken;
use App\Support\Tesoreria\TesoreriaConfig;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class MovimientoIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public bool $formAbierto = false;

    public ?int $idPacientes = null;

    public int $tipoRegistro = Paciente::TIPO_INGRESO;

    public string $fecha = '';

    public string $hora = '';

    public ?int $idClientes = null;

    public ?int $idCuentas = null;

    public ?int $idCuentasdetalle = null;

    public string $pagado = '';

    public ?int $idMediodepago = null;

    public string $observaciones = '';

    public string $busqueda = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaPacientes(), 404);
        $this->reiniciarFechaHora();
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatedTipoRegistro(): void
    {
        if ((int) $this->tipoRegistro === Paciente::TIPO_EGRESO) {
            $this->idClientes = Paciente::ID_CLIENTES_EGRESO;
        } else {
            $this->idCuentas = null;
            $this->idCuentasdetalle = null;
        }
    }

    public function updatedIdCuentas(): void
    {
        $this->idCuentasdetalle = null;
    }

    public function abrirFormularioNuevo(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $this->resetFormulario();
        $this->tipoRegistro = Paciente::TIPO_INGRESO;
        $this->reiniciarFechaHora();
        $this->formAbierto = true;
        $this->resetErrorBag();
    }

    public function abrirFormularioEditar(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $movimiento = $this->movimientoEnAlcance($id);
        if ($movimiento === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontr? el movimiento.');

            return;
        }

        $this->idPacientes = (int) $movimiento->idPacientes;
        $this->tipoRegistro = (int) $movimiento->tipoRegistro;
        $this->fecha = $movimiento->fechhoy?->format('Y-m-d') ?? now()->toDateString();
        $this->hora = $movimiento->fechhoy?->format('H:i:s') ?? now()->format('H:i:s');
        $this->idClientes = (int) ($movimiento->idClientes ?: 0) ?: null;
        $this->idCuentasdetalle = ((int) ($movimiento->idCuentasdetalle ?? 0) > 0)
            ? (int) $movimiento->idCuentasdetalle
            : null;
        $this->idCuentas = $movimiento->cuentaDetalle?->idCuentas
            ? (int) $movimiento->cuentaDetalle->idCuentas
            : null;
        $this->pagado = number_format((float) $movimiento->pagado, 2, ',', '');
        $this->idMediodepago = ((int) ($movimiento->idMediodepago ?? 0) > 0)
            ? (int) $movimiento->idMediodepago
            : null;
        $this->observaciones = (string) ($movimiento->observaciones ?? '');
        $this->formAbierto = true;
        $this->resetErrorBag();
    }

    public function cancelarFormulario(): void
    {
        $this->formAbierto = false;
        $this->resetFormulario();
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'tesoreria-movimientos:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $this->pagado = $this->normalizarImporte($this->pagado);
        $this->hora = $this->normalizarHora($this->hora);
        $esEgreso = (int) $this->tipoRegistro === Paciente::TIPO_EGRESO;

        if ($esEgreso) {
            $this->idClientes = Paciente::ID_CLIENTES_EGRESO;
        }

        $reglas = [
            'tipoRegistro' => ['required', 'integer', Rule::in([Paciente::TIPO_INGRESO, Paciente::TIPO_EGRESO])],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'pagado' => ['required', 'numeric', 'gt:0'],
            'idMediodepago' => ['required', 'integer', 'exists:mediodepago,id'],
            'observaciones' => ['nullable', 'string', 'max:65535'],
        ];

        if ($esEgreso) {
            $reglas['idCuentas'] = ['required', 'integer', 'exists:cuentas,id'];
            $reglas['idCuentasdetalle'] = [
                'required',
                'integer',
                Rule::exists('cuentasdetalle', 'id')->where(fn ($q) => $q->where('idCuentas', (int) $this->idCuentas)),
            ];
        } else {
            $reglas['idClientes'] = ['required', 'integer', 'exists:clientes,idClientes'];
        }

        $this->validate($reglas, [
            'tipoRegistro.required' => 'Seleccione el tipo de movimiento.',
            'tipoRegistro.in' => 'El tipo de movimiento no es v?lido.',
            'fecha.required' => 'Ingrese la fecha.',
            'fecha.date_format' => 'La fecha no es v?lida.',
            'hora.required' => 'Ingrese la hora.',
            'hora.date_format' => 'La hora no es v?lida.',
            'idClientes.required' => 'Seleccione el cliente.',
            'idClientes.exists' => 'El cliente seleccionado no es v?lido.',
            'idCuentas.required' => 'Seleccione la cuenta.',
            'idCuentas.exists' => 'La cuenta seleccionada no es v?lida.',
            'idCuentasdetalle.required' => 'Seleccione el proveedor.',
            'idCuentasdetalle.exists' => 'El proveedor seleccionado no es v?lido.',
            'pagado.required' => 'Ingrese el importe pagado.',
            'pagado.numeric' => 'El importe no es v?lido.',
            'pagado.gt' => 'El importe debe ser mayor a cero.',
            'idMediodepago.required' => 'Seleccione el medio de pago.',
            'idMediodepago.exists' => 'El medio de pago seleccionado no es v?lido.',
        ]);

        RateLimiter::hit($key, 60);

        $fechhoy = $this->fecha.' '.$this->hora;
        $payload = [
            'tipoRegistro' => (int) $this->tipoRegistro,
            'fechhoy' => $fechhoy,
            'idClientes' => $esEgreso
                ? Paciente::ID_CLIENTES_EGRESO
                : (int) $this->idClientes,
            'idCuentasdetalle' => $esEgreso ? (int) $this->idCuentasdetalle : 0,
            'pagado' => round((float) $this->pagado, 2),
            'idMediodepago' => (int) $this->idMediodepago,
            'observaciones' => trim($this->observaciones) !== '' ? trim($this->observaciones) : null,
            'estado' => $esEgreso ? 'Egreso' : 'Pago',
            'precio' => 0,
            'descuento' => 0,
            'saldo' => 0,
        ];

        if ($this->idPacientes !== null) {
            $movimiento = $this->movimientoEnAlcance($this->idPacientes);
            if ($movimiento === null) {
                $this->dispatch('vl-swal-error', mensaje: 'No se encontr? el movimiento.');
                $this->cancelarFormulario();

                return;
            }

            $movimiento->update($payload);
            $mensaje = 'Movimiento actualizado correctamente.';
        } else {
            Paciente::create($payload);
            $mensaje = 'Movimiento registrado correctamente.';
        }

        $this->cancelarFormulario();
        $this->resetPage();
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);
    }

    public function facturacionPlaceholder(): void
    {
        $this->dispatch(
            'vl-swal-error',
            mensaje: 'La facturación desde movimientos aún no está disponible en SILAVET.'
        );
    }

    public function render()
    {
        $movimientos = Paciente::query()
            ->with([
                'cliente:idClientes,nombre,cuit',
                'medioDePago:id,nombreMedioPago',
                'cuentaDetalle:id,idCuentas,nombreCuentasDetalle',
                'cuentaDetalle.cuenta:id,nombreCuenta',
            ])
            ->whereIn('tipoRegistro', [Paciente::TIPO_INGRESO, Paciente::TIPO_EGRESO])
            ->when(trim($this->busqueda) !== '', function ($q) {
                $term = trim($this->busqueda);
                $q->where(function ($inner) use ($term) {
                    $inner->where('observaciones', 'like', "%{$term}%")
                        ->orWhere('pagado', 'like', "%{$term}%")
                        ->orWhereHas('cliente', function ($c) use ($term) {
                            $c->where('nombre', 'like', "%{$term}%")
                                ->orWhere('cuit', 'like', "%{$term}%");
                        })
                        ->orWhereHas('medioDePago', function ($m) use ($term) {
                            $m->where('nombreMedioPago', 'like', "%{$term}%");
                        })
                        ->orWhereHas('cuentaDetalle', function ($d) use ($term) {
                            $d->where('nombreCuentasDetalle', 'like', "%{$term}%")
                                ->orWhereHas('cuenta', fn ($c) => $c->where('nombreCuenta', 'like', "%{$term}%"));
                        });

                    if (ctype_digit($term)) {
                        $inner->orWhere('idPacientes', (int) $term);
                    }
                });
            })
            ->orderByDesc('fechhoy')
            ->orderByDesc('idPacientes')
            ->paginate(self::POR_PAGINA);

        $clientes = Cliente::query()
            ->orderBy('nombre')
            ->get(['idClientes', 'nombre']);

        $cuentas = Schema::hasTable('cuentas')
            ? Cuenta::query()->orderBy('nombreCuenta')->get(['id', 'nombreCuenta'])
            : collect();

        $proveedores = ($this->idCuentas && Schema::hasTable('cuentasdetalle'))
            ? CuentaDetalle::query()
                ->where('idCuentas', (int) $this->idCuentas)
                ->orderBy('nombreCuentasDetalle')
                ->get(['id', 'nombreCuentasDetalle'])
            : collect();

        $mediosPago = Schema::hasTable('mediodepago')
            ? MedioDePago::query()->orderBy('nombreMedioPago')->get(['id', 'nombreMedioPago'])
            : collect();

        $mostrarColumnaAfip = FacturacionAfipConfig::habilitada()
            && FacturacionAfipConfig::esModoMovimiento();

        $afipEmitidos = $mostrarColumnaAfip
            ? FacturacionAfipIndicadores::mapaConEmitido($movimientos->getCollection()->pluck('idPacientes')->all())
            : [];

        return view('livewire.tesoreria.movimiento-index', [
            'movimientos' => $movimientos,
            'clientes' => $clientes,
            'cuentas' => $cuentas,
            'proveedores' => $proveedores,
            'mediosPago' => $mediosPago,
            'mostrarColumnaAfip' => $mostrarColumnaAfip,
            'afipEmitidos' => $afipEmitidos,
            'urlAfipFn' => static fn (int $id): string => route('facturacion.afip.comprobantes', [
                'ref' => OpaqueRouteToken::forCompAfipPaciente($id),
            ]),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function movimientoEnAlcance(int $id): ?Paciente
    {
        return Paciente::query()
            ->with(['cuentaDetalle:id,idCuentas,nombreCuentasDetalle'])
            ->whereKey($id)
            ->whereIn('tipoRegistro', [Paciente::TIPO_INGRESO, Paciente::TIPO_EGRESO])
            ->first();
    }

    private function resetFormulario(): void
    {
        $this->idPacientes = null;
        $this->tipoRegistro = Paciente::TIPO_INGRESO;
        $this->idClientes = null;
        $this->idCuentas = null;
        $this->idCuentasdetalle = null;
        $this->pagado = '';
        $this->idMediodepago = null;
        $this->observaciones = '';
        $this->reiniciarFechaHora();
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
