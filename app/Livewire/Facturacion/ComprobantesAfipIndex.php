<?php

namespace App\Livewire\Facturacion;

use App\Models\Cliente;
use App\Models\CompAfip;
use App\Models\Movimiento;
use App\Models\Paciente;
use App\Support\CuitInput;
use App\Support\DniInput;
use App\Support\Facturacion\FacturacionAfipConfig;
use App\Support\Facturacion\FacturacionAfipService;
use App\Support\Facturacion\FacturacionIva;
use App\Support\PermisosIaCatalog;
use App\Support\Protocolos\PacienteListadoFiltros;
use App\Support\Security\OpaqueRouteToken;
use App\Support\Tesoreria\MovimientoListadoFiltros;
use App\Support\Tesoreria\MovimientosCajaListadoFiltros;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use RuntimeException;

class ComprobantesAfipIndex extends Component
{
    public int $idPacientes = 0;

    public int $idMovimientos = 0;

    public string $origenLabel = '';

    public string $clienteLabel = '';

    public string $importeLabel = '';

    public string $volverUrl = '';

    public ?int $idFacturaNc = null;

    /** @var 'cliente'|'paciente'|'consumidor_final' */
    public string $tipoReceptor = FacturacionAfipConfig::RECEPTOR_CONSUMIDOR_FINAL;

    public bool $puedeReceptorCliente = false;

    public bool $puedeReceptorPaciente = false;

    public int $idClientesReceptor = 0;

    public int $idPacientesReceptor = 0;

    public string $cuitClienteEdit = '';

    public string $dniClienteEdit = '';

    public string $cuitPacienteEdit = '';

    public string $dniPacienteEdit = '';

    /** Condición IVA del receptor. Solo se usa si el tenant es responsable inscripto. 0 = sin elegir. */
    public int $condicionIvaReceptorId = 5;

    public function mount(string $ref): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(FacturacionAfipConfig::habilitada(), 403);

        if (FacturacionAfipConfig::esModoMovimientoCaja()) {
            $this->mountDesdeMovimientoCaja($ref);

            return;
        }

        $decoded = OpaqueRouteToken::decodeCompAfipPaciente($ref);
        abort_if($decoded === null, 404);

        $this->idPacientes = (int) $decoded['id'];
        $paciente = Paciente::query()
            ->with('cliente:idClientes,nombre,cuit,dni')
            ->find($this->idPacientes);
        abort_if($paciente === null, 404);

        $tipo = (int) $paciente->tipoRegistro;
        if (FacturacionAfipConfig::esModoMovimiento()) {
            abort_unless($tipo === Paciente::TIPO_INGRESO, 404);
            $this->volverUrl = MovimientoListadoFiltros::urlIndex();
            $this->origenLabel = 'Ingreso #'.$paciente->idPacientes;
        } else {
            abort_unless(in_array($tipo, [Paciente::TIPO_PROTOCOLO, Paciente::TIPO_INGRESO], true), 404);
            $this->volverUrl = PacienteListadoFiltros::urlIndex(
                PacienteListadoFiltros::desdeRequest(),
                $this->idPacientes
            );
            $this->origenLabel = $tipo === Paciente::TIPO_PROTOCOLO
                ? ('Protocolo '.($paciente->nombreProtocolo ?: '#'.$paciente->idPacientes))
                : ('Pago global #'.$paciente->idPacientes);
        }

        $this->clienteLabel = $paciente->cliente?->nombre
            ?: (trim((string) $paciente->propietario) ?: (trim((string) $paciente->nombre) ?: '—'));

        if (FacturacionAfipConfig::esModoPaciente()) {
            $this->puedeReceptorCliente = $paciente->cliente instanceof Cliente;
            $this->puedeReceptorPaciente = $tipo === Paciente::TIPO_PROTOCOLO;
            $this->idClientesReceptor = (int) ($paciente->idClientes ?? 0);
            $this->tipoReceptor = $this->puedeReceptorPaciente
                ? FacturacionAfipConfig::RECEPTOR_PACIENTE
                : FacturacionAfipConfig::RECEPTOR_CLIENTE;
            $this->cargarDatosFacturacion($paciente);
        }

        $importe = $tipo === Paciente::TIPO_INGRESO
            ? (float) $paciente->pagado
            : (float) $paciente->precio;
        $this->importeLabel = '$ '.number_format(abs($importe), 2, ',', '.');

        if (FacturacionAfipConfig::esResponsableInscripto()) {
            $identificacion = $this->identificacionPaciente($paciente);
            $this->condicionIvaReceptorId = $this->condicionSugerida(
                $identificacion['es_cuit'],
                $identificacion['es_consumidor_final']
            );
        }
    }

    private function mountDesdeMovimientoCaja(string $ref): void
    {
        $decoded = OpaqueRouteToken::decodeCompAfipMovimiento($ref);
        abort_if($decoded === null, 404);

        $this->idMovimientos = (int) $decoded['id'];
        $movimiento = Movimiento::query()
            ->with([
                'cliente:idClientes,nombre,cuit,dni',
                'paciente:idPacientes,nombre,propietario,dni',
                'concepto:id,concepto',
            ])
            ->find($this->idMovimientos);
        abort_if($movimiento === null || ! $movimiento->esIngreso(), 404);

        $this->volverUrl = MovimientosCajaListadoFiltros::urlIndex();
        $this->origenLabel = 'Ingreso #'.$movimiento->id
            .($movimiento->concepto?->concepto ? ' · '.$movimiento->concepto->concepto : '');

        $this->puedeReceptorCliente = (int) ($movimiento->idClientes ?? 0) > 0;
        $this->puedeReceptorPaciente = (int) ($movimiento->idPacientes ?? 0) > 0;
        $this->idClientesReceptor = $this->puedeReceptorCliente ? (int) $movimiento->idClientes : 0;
        $this->idPacientesReceptor = $this->puedeReceptorPaciente ? (int) $movimiento->idPacientes : 0;

        $this->tipoReceptor = FacturacionAfipConfig::RECEPTOR_CONSUMIDOR_FINAL;

        $partesCliente = [];
        if ($this->puedeReceptorCliente) {
            $partesCliente[] = $movimiento->cliente?->nombre ?: 'Cliente #'.$movimiento->idClientes;
        }
        if ($this->puedeReceptorPaciente) {
            $etiquetaPac = trim((string) ($movimiento->paciente?->nombre ?? ''));
            $partesCliente[] = $etiquetaPac !== ''
                ? ('Paciente: '.$etiquetaPac)
                : ('Protocolo #'.$movimiento->idPacientes);
        }
        $this->clienteLabel = $partesCliente !== [] ? implode(' · ', $partesCliente) : '—';

        $this->importeLabel = '$ '.number_format(abs((float) $movimiento->monto), 2, ',', '.');

        if (FacturacionAfipConfig::esResponsableInscripto()) {
            $this->condicionIvaReceptorId = FacturacionIva::CONDICION_CONSUMIDOR_FINAL;
        }
    }

    public function updatedTipoReceptor(): void
    {
        if (! FacturacionAfipConfig::esResponsableInscripto()) {
            return;
        }

        if (FacturacionAfipConfig::esModoMovimientoCaja()) {
            $identificacion = $this->identificacionCaja($this->documentosReceptorCaja());
        } elseif (FacturacionAfipConfig::esModoPaciente()) {
            $paciente = Paciente::query()
                ->with('cliente:idClientes,nombre,cuit,dni')
                ->find($this->idPacientes);
            $identificacion = $paciente instanceof Paciente
                ? $this->identificacionPaciente($paciente)
                : ['es_cuit' => false, 'es_consumidor_final' => true];
        } else {
            return;
        }
        $this->condicionIvaReceptorId = $this->condicionSugerida(
            $identificacion['es_cuit'],
            $identificacion['es_consumidor_final']
        );
    }

    public function updatedCuitClienteEdit(string $value): void
    {
        $this->cuitClienteEdit = CuitInput::format($value);
    }

    public function updatedDniClienteEdit(string $value): void
    {
        $this->dniClienteEdit = DniInput::normalize($value, 8);
    }

    public function updatedCuitPacienteEdit(string $value): void
    {
        $this->cuitPacienteEdit = CuitInput::format($value);
    }

    public function updatedDniPacienteEdit(string $value): void
    {
        $this->dniPacienteEdit = DniInput::normalize($value, 8);
    }

    public function guardarDatosCliente(): void
    {
        abort_unless(FacturacionAfipConfig::esModoPaciente(), 403);
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        if ($this->idClientesReceptor <= 0) {
            $this->dispatch('vl-swal-error', mensaje: 'Este registro no tiene cliente asociado.');

            return;
        }

        $this->cuitClienteEdit = CuitInput::format($this->cuitClienteEdit);
        $this->dniClienteEdit = DniInput::normalize(trim($this->dniClienteEdit), 8);

        $this->validate([
            'cuitClienteEdit' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if ((string) $value !== '' && ! CuitInput::isComplete((string) $value)) {
                    $fail('El CUIT del cliente debe tener 11 dígitos.');
                }
            }],
            'dniClienteEdit' => ['nullable', 'string', 'max:8'],
        ], [
            'dniClienteEdit.max' => 'El DNI del cliente no puede superar 8 caracteres.',
        ]);

        $this->persistirIdentificacion(
            'clientes',
            $this->idClientesReceptor,
            CuitInput::normalize($this->cuitClienteEdit),
            $this->dniClienteEdit,
            'cuitClienteEdit',
            'dniClienteEdit',
            'cliente',
            'facturacion-afip-datos-cliente:'
        );
    }

    public function guardarDatosPaciente(): void
    {
        abort_unless(FacturacionAfipConfig::esModoPaciente(), 403);
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        if ($this->idPacientes <= 0) {
            $this->dispatch('vl-swal-error', mensaje: 'No hay protocolo para guardar.');

            return;
        }

        $this->cuitPacienteEdit = CuitInput::format($this->cuitPacienteEdit);
        $this->dniPacienteEdit = DniInput::normalize(trim($this->dniPacienteEdit), 8);

        $this->validate([
            'cuitPacienteEdit' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if ((string) $value !== '' && ! CuitInput::isComplete((string) $value)) {
                    $fail('El CUIT del paciente debe tener 11 dígitos.');
                }
            }],
            'dniPacienteEdit' => ['nullable', 'string', 'max:8'],
        ], [
            'dniPacienteEdit.max' => 'El DNI del paciente no puede superar 8 caracteres.',
        ]);

        $this->persistirIdentificacion(
            'pacientes',
            $this->idPacientes,
            CuitInput::normalize($this->cuitPacienteEdit),
            $this->dniPacienteEdit,
            'cuitPacienteEdit',
            'dniPacienteEdit',
            'paciente',
            'facturacion-afip-datos-paciente:'
        );
    }

    public function guardarDniCliente(): void
    {
        $this->persistirDniCliente(false);
    }

    public function guardarDniPaciente(): void
    {
        $this->persistirDniPaciente(false);
    }

    private function persistirDniCliente(bool $silencioso): void
    {
        abort_unless(FacturacionAfipConfig::esModoMovimientoCaja(), 403);
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        if ($this->idClientesReceptor <= 0) {
            if (! $silencioso) {
                $this->dispatch('vl-swal-error', mensaje: 'Este movimiento no tiene cliente asociado.');
            }

            throw new RuntimeException('Este movimiento no tiene cliente asociado.');
        }

        if (! Schema::hasColumn('clientes', 'dni')) {
            $mensaje = 'Falta la columna clientes.dni. Ejecute database/sql/dni_cuit_pacientes_clientes.sql.';
            if (! $silencioso) {
                $this->dispatch('vl-swal-error', mensaje: $mensaje);
            }

            throw new RuntimeException($mensaje);
        }

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'facturacion-afip-dni-cliente:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $dni = trim($this->dniClienteEdit);
        $this->validate([
            'dniClienteEdit' => ['required', 'string', 'max:8'],
        ], [
            'dniClienteEdit.required' => 'Ingrese el DNI del cliente.',
            'dniClienteEdit.max' => 'El DNI no puede superar 8 caracteres.',
        ]);

        Cliente::query()->whereKey($this->idClientesReceptor)->update(['dni' => $dni]);
        RateLimiter::hit($key, 60);
        $this->dniClienteEdit = '';

        if (! $silencioso) {
            $this->dispatch('vl-swal-exito', mensaje: 'DNI del cliente guardado correctamente.');
        }
    }

    private function persistirDniPaciente(bool $silencioso): void
    {
        abort_unless(FacturacionAfipConfig::esModoMovimientoCaja(), 403);
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        if ($this->idPacientesReceptor <= 0) {
            if (! $silencioso) {
                $this->dispatch('vl-swal-error', mensaje: 'Este movimiento no tiene protocolo/paciente asociado.');
            }

            throw new RuntimeException('Este movimiento no tiene protocolo/paciente asociado.');
        }

        if (! Schema::hasColumn('pacientes', 'dni')) {
            $mensaje = 'Falta la columna pacientes.dni. Ejecute database/sql/pacientes_dni.sql.';
            if (! $silencioso) {
                $this->dispatch('vl-swal-error', mensaje: $mensaje);
            }

            throw new RuntimeException($mensaje);
        }

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'facturacion-afip-dni-paciente:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $dni = trim($this->dniPacienteEdit);
        $this->validate([
            'dniPacienteEdit' => ['required', 'string', 'max:8'],
        ], [
            'dniPacienteEdit.required' => 'Ingrese el DNI del paciente.',
            'dniPacienteEdit.max' => 'El DNI no puede superar 8 caracteres.',
        ]);

        Paciente::query()->whereKey($this->idPacientesReceptor)->update(['dni' => $dni]);
        RateLimiter::hit($key, 60);
        $this->dniPacienteEdit = '';

        if (! $silencioso) {
            $this->dispatch('vl-swal-exito', mensaje: 'DNI del paciente guardado correctamente.');
        }
    }

    public function emitirFactura(): void
    {
        if (FacturacionAfipConfig::esModoMovimientoCaja()) {
            try {
                $this->asegurarIdentificacionReceptorCaja();
            } catch (RuntimeException $e) {
                $this->dispatch('vl-swal-error', mensaje: $e->getMessage());

                return;
            }

            $tipo = $this->tipoReceptorValidado();
            if (FacturacionAfipConfig::esResponsableInscripto()) {
                $condicion = $this->condicionIvaReceptorId;
                $this->ejecutarEmision(fn (FacturacionAfipService $svc) => $svc->emitirFacturaCaja(
                    $this->idMovimientos,
                    $tipo,
                    null,
                    $condicion
                ));

                return;
            }

            $this->ejecutarEmision(fn (FacturacionAfipService $svc) => $svc->emitirFacturaCaja(
                $this->idMovimientos,
                $tipo
            ));

            return;
        }

        $condicion = FacturacionAfipConfig::esResponsableInscripto()
            ? $this->condicionIvaReceptorId
            : null;

        $this->ejecutarEmision(function (FacturacionAfipService $svc) use ($condicion) {
            $tipo = FacturacionAfipConfig::esModoPaciente() ? $this->tipoReceptorValidado() : null;

            return $svc->emitirFactura($this->idPacientes, null, $condicion, $tipo);
        });
    }

    public function emitirComanda(): void
    {
        if (FacturacionAfipConfig::esModoMovimientoCaja()) {
            try {
                $this->asegurarIdentificacionReceptorCaja();
            } catch (RuntimeException $e) {
                $this->dispatch('vl-swal-error', mensaje: $e->getMessage());

                return;
            }

            $this->ejecutarEmision(fn (FacturacionAfipService $svc) => $svc->emitirComandaCaja(
                $this->idMovimientos,
                $this->tipoReceptorValidado()
            ));

            return;
        }

        $this->ejecutarEmision(function (FacturacionAfipService $svc) {
            $tipo = FacturacionAfipConfig::esModoPaciente() ? $this->tipoReceptorValidado() : null;

            return $svc->emitirComanda($this->idPacientes, null, $tipo);
        });
    }

    public function emitirNotaCredito(): void
    {
        $id = (int) ($this->idFacturaNc ?? 0);
        if ($id <= 0) {
            $this->dispatch('vl-swal-error', mensaje: 'Seleccione la factura a anular.');

            return;
        }

        $this->ejecutarEmision(function (FacturacionAfipService $svc) use ($id) {
            $resultado = $svc->emitirNotaCredito($id);
            $this->idFacturaNc = null;

            return $resultado;
        });
    }

    /**
     * @param  callable(FacturacionAfipService): array{comp: CompAfip, mensaje: string}  $accion
     */
    private function ejecutarEmision(callable $accion): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $key = 'facturacion-afip-emitir:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->dispatch('vl-swal-error', mensaje: 'Demasiados intentos. Espere un momento.');

            return;
        }

        try {
            $resultado = $accion(app(FacturacionAfipService::class));
            RateLimiter::hit($key, 60);
            $this->dispatch('vl-swal-exito', mensaje: $resultado['mensaje']);
        } catch (RuntimeException $e) {
            $this->dispatch('vl-swal-error', mensaje: $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('vl-swal-error', mensaje: 'No se pudo emitir el comprobante.');
        }
    }

    private function tipoReceptorValidado(): string
    {
        $tipo = $this->tipoReceptor;
        if (! FacturacionAfipConfig::esTipoReceptorCajaValido($tipo)) {
            throw new RuntimeException('Seleccione a quién se factura.');
        }

        if ($tipo === FacturacionAfipConfig::RECEPTOR_CLIENTE && ! $this->puedeReceptorCliente) {
            throw new RuntimeException('Este movimiento no tiene cliente asociado.');
        }

        if ($tipo === FacturacionAfipConfig::RECEPTOR_PACIENTE && ! $this->puedeReceptorPaciente) {
            throw new RuntimeException('Este movimiento no tiene protocolo/paciente asociado.');
        }

        return $tipo;
    }

    private function asegurarIdentificacionReceptorCaja(): void
    {
        $tipo = $this->tipoReceptorValidado();

        if ($tipo === FacturacionAfipConfig::RECEPTOR_CONSUMIDOR_FINAL) {
            return;
        }

        if ($tipo === FacturacionAfipConfig::RECEPTOR_CLIENTE && trim($this->dniClienteEdit) !== '') {
            $this->persistirDniCliente(true);
        }

        if ($tipo === FacturacionAfipConfig::RECEPTOR_PACIENTE && trim($this->dniPacienteEdit) !== '') {
            $this->persistirDniPaciente(true);
        }

        $docs = $this->documentosReceptorCaja();

        if ($tipo === FacturacionAfipConfig::RECEPTOR_CLIENTE && ! $docs['clienteTieneIdentificacion']) {
            throw new RuntimeException(
                'El cliente no tiene DNI ni CUIT cargados. Ingrese el DNI y guárdelo antes de emitir.'
            );
        }

        if ($tipo === FacturacionAfipConfig::RECEPTOR_PACIENTE && ! $docs['pacienteTieneIdentificacion']) {
            throw new RuntimeException(
                'El paciente no tiene DNI cargado. Ingrese el DNI y guárdelo antes de emitir.'
            );
        }
    }

    /**
     * @return array{
     *     clienteDni: string,
     *     clienteCuit: string,
     *     clienteCuitFmt: string,
     *     pacienteDni: string,
     *     clienteTieneIdentificacion: bool,
     *     pacienteTieneIdentificacion: bool,
     *     clienteNombre: string,
     *     pacienteNombre: string,
     * }
     */
    private function documentosReceptorCaja(): array
    {
        $clienteDni = '';
        $clienteCuit = '';
        $clienteNombre = '';
        $pacienteDni = '';
        $pacienteNombre = '';

        if ($this->idMovimientos > 0) {
            $movimiento = Movimiento::query()
                ->with([
                    'cliente:idClientes,nombre,cuit,dni',
                    'paciente:idPacientes,nombre,propietario,dni',
                ])
                ->find($this->idMovimientos);

            if ($movimiento !== null) {
                if ($movimiento->cliente instanceof Cliente) {
                    $clienteNombre = trim((string) $movimiento->cliente->nombre);
                    $clienteDni = $this->docNormalizado((string) ($movimiento->cliente->dni ?? ''));
                    $clienteCuit = $this->docNormalizado((string) ($movimiento->cliente->cuit ?? ''));
                }

                if ($movimiento->paciente instanceof Paciente) {
                    $pacienteNombre = trim((string) ($movimiento->paciente->propietario ?: $movimiento->paciente->nombre));
                    $pacienteDni = $this->docNormalizado((string) ($movimiento->paciente->dni ?? ''));
                }
            }
        }

        return [
            'clienteDni' => $clienteDni,
            'clienteCuit' => $clienteCuit,
            'clienteCuitFmt' => CuitInput::format($clienteCuit),
            'pacienteDni' => $pacienteDni,
            'clienteTieneIdentificacion' => $clienteDni !== '' || $clienteCuit !== '',
            'pacienteTieneIdentificacion' => $pacienteDni !== '',
            'clienteNombre' => $clienteNombre,
            'pacienteNombre' => $pacienteNombre,
        ];
    }

    private function docNormalizado(string $raw): string
    {
        $doc = preg_replace('/\D/', '', $raw) ?? '';

        return ($doc === '' || $doc === '0') ? '' : $doc;
    }

    private function docPreferido(string ...$valores): string
    {
        foreach ($valores as $raw) {
            $doc = $this->docNormalizado($raw);
            if ($doc !== '') {
                return $doc;
            }
        }

        return '';
    }

    /**
     * @return array{es_cuit: bool, es_consumidor_final: bool}
     */
    private function identificacionPaciente(Paciente $paciente): array
    {
        if ($this->tipoReceptor === FacturacionAfipConfig::RECEPTOR_CLIENTE) {
            $cliente = $paciente->cliente;
            $docCliente = $cliente instanceof Cliente
                ? $this->docPreferido((string) ($cliente->cuit ?? ''), (string) ($cliente->dni ?? ''))
                : '';

            return [
                'es_cuit' => strlen($docCliente) === 11,
                'es_consumidor_final' => $docCliente === '',
            ];
        }

        $docPaciente = (int) $paciente->tipoRegistro === Paciente::TIPO_PROTOCOLO
            ? $this->docNormalizado((string) ($paciente->dni ?? ''))
            : '';
        $cuitPaciente = '';
        if ((int) $paciente->tipoRegistro === Paciente::TIPO_PROTOCOLO && Schema::hasColumn('pacientes', 'cuit')) {
            $cuitPaciente = $this->docNormalizado((string) ($paciente->cuit ?? ''));
            if (strlen($cuitPaciente) !== 11) {
                $cuitPaciente = '';
            }
        }
        if ($cuitPaciente === '' && strlen($docPaciente) === 11) {
            $cuitPaciente = $docPaciente;
        }
        $cliente = $paciente->cliente;
        $docCliente = $cliente instanceof Cliente
            ? $this->docPreferido((string) ($cliente->cuit ?? ''), (string) ($cliente->dni ?? ''))
            : '';

        if ((int) $paciente->tipoRegistro !== Paciente::TIPO_PROTOCOLO) {
            return [
                'es_cuit' => strlen($docCliente) === 11,
                'es_consumidor_final' => $docCliente === '',
            ];
        }

        $doc = $docPaciente !== '' ? $docPaciente : $cuitPaciente;

        return [
            'es_cuit' => $cuitPaciente !== '',
            'es_consumidor_final' => $doc === '',
        ];
    }

    /**
     * @param  array<string, mixed>  $docs
     * @return array{es_cuit: bool, es_consumidor_final: bool}
     */
    private function identificacionCaja(array $docs): array
    {
        $tipo = $this->tipoReceptor;
        if ($tipo === FacturacionAfipConfig::RECEPTOR_CONSUMIDOR_FINAL) {
            return ['es_cuit' => false, 'es_consumidor_final' => true];
        }

        if ($tipo === FacturacionAfipConfig::RECEPTOR_CLIENTE) {
            $doc = $this->docPreferido(
                (string) ($docs['clienteCuit'] ?? ''),
                (string) ($docs['clienteDni'] ?? '')
            );
        } else {
            $doc = $this->docNormalizado((string) ($docs['pacienteDni'] ?? ''));
        }

        return [
            'es_cuit' => strlen($doc) === 11,
            'es_consumidor_final' => $doc === '',
        ];
    }

    private function importeOrigen(): float
    {
        if (FacturacionAfipConfig::esModoMovimientoCaja()) {
            $monto = Movimiento::query()->whereKey($this->idMovimientos)->value('monto');

            return round(abs((float) $monto), 2);
        }

        $paciente = Paciente::query()->find($this->idPacientes);
        if (! $paciente instanceof Paciente) {
            return 0.0;
        }

        $importe = (int) $paciente->tipoRegistro === Paciente::TIPO_INGRESO
            ? (float) $paciente->pagado
            : (float) $paciente->precio;

        return round(abs($importe), 2);
    }

    private function condicionSugerida(bool $esCuit, bool $esConsumidorFinal): int
    {
        if ($esConsumidorFinal || ! $esCuit) {
            return FacturacionIva::CONDICION_CONSUMIDOR_FINAL;
        }

        return 0;
    }

    private function cargarDatosFacturacion(Paciente $paciente): void
    {
        $cliente = $paciente->cliente;
        $this->cuitClienteEdit = $cliente instanceof Cliente
            ? CuitInput::format((string) ($cliente->cuit ?? ''))
            : '';
        $this->dniClienteEdit = $cliente instanceof Cliente
            ? DniInput::normalize((string) ($cliente->dni ?? ''), 8)
            : '';
        $this->cuitPacienteEdit = Schema::hasColumn('pacientes', 'cuit')
            ? CuitInput::format((string) ($paciente->cuit ?? ''))
            : '';
        $this->dniPacienteEdit = DniInput::normalize((string) ($paciente->dni ?? ''), 8);
    }

    private function persistirIdentificacion(
        string $tabla,
        int $id,
        string $cuit,
        string $dni,
        string $campoCuit,
        string $campoDni,
        string $etiqueta,
        string $ratePrefix
    ): void {
        if ($cuit !== '' && ! Schema::hasColumn($tabla, 'cuit')) {
            $mensaje = 'No se puede guardar el CUIT: falta la columna '.$tabla.'.cuit. '
                .'Ejecute database/sql/dni_cuit_pacientes_clientes.sql.';
            $this->dispatch('vl-swal-error', mensaje: $mensaje);
            throw \Illuminate\Validation\ValidationException::withMessages([$campoCuit => $mensaje]);
        }

        if ($dni !== '' && ! Schema::hasColumn($tabla, 'dni')) {
            $mensaje = 'No se puede guardar el DNI: falta la columna '.$tabla.'.dni. '
                .'Ejecute database/sql/dni_cuit_pacientes_clientes.sql.';
            $this->dispatch('vl-swal-error', mensaje: $mensaje);
            throw \Illuminate\Validation\ValidationException::withMessages([$campoDni => $mensaje]);
        }

        $uid = labCtx()->idUsuarios ?? 0;
        $key = $ratePrefix.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $payload = [];
        if (Schema::hasColumn($tabla, 'cuit')) {
            $payload['cuit'] = $cuit;
        }
        if (Schema::hasColumn($tabla, 'dni')) {
            $payload['dni'] = $dni;
        }

        if ($payload === []) {
            $this->dispatch('vl-swal-error', mensaje: 'No hay columnas de CUIT ni DNI para guardar en '.$tabla.'.');

            return;
        }

        if ($tabla === 'clientes') {
            Cliente::query()->whereKey($id)->update($payload);
        } else {
            Paciente::query()->whereKey($id)->update($payload);
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: 'Datos de facturación del '.$etiqueta.' guardados correctamente.');
    }

    /**
     * @return array{
     *     clienteNombre: string,
     *     clienteCuitFmt: string,
     *     clienteDni: string,
     *     pacienteNombre: string,
     *     pacienteCuitFmt: string,
     *     pacienteDni: string
     * }
     */
    private function documentosEleccion(): array
    {
        $vacio = [
            'clienteNombre' => '',
            'clienteCuitFmt' => '',
            'clienteDni' => '',
            'pacienteNombre' => '',
            'pacienteCuitFmt' => '',
            'pacienteDni' => '',
        ];

        $paciente = Paciente::query()
            ->with('cliente:idClientes,nombre,cuit,dni')
            ->find($this->idPacientes);
        if (! $paciente instanceof Paciente) {
            return $vacio;
        }

        $cliente = $paciente->cliente;
        $cuitPaciente = Schema::hasColumn('pacientes', 'cuit')
            ? $this->docNormalizado((string) ($paciente->cuit ?? ''))
            : '';

        return [
            'clienteNombre' => $cliente instanceof Cliente ? trim((string) $cliente->nombre) : '',
            'clienteCuitFmt' => $cliente instanceof Cliente
                ? CuitInput::format($this->docNormalizado((string) ($cliente->cuit ?? '')))
                : '',
            'clienteDni' => $cliente instanceof Cliente
                ? $this->docNormalizado((string) ($cliente->dni ?? ''))
                : '',
            'pacienteNombre' => trim((string) ($paciente->propietario ?: $paciente->nombre)),
            'pacienteCuitFmt' => strlen($cuitPaciente) === 11 ? CuitInput::format($cuitPaciente) : '',
            'pacienteDni' => $this->docNormalizado((string) ($paciente->dni ?? '')),
        ];
    }

    public function render()
    {
        $svc = app(FacturacionAfipService::class);

        if (FacturacionAfipConfig::esModoMovimientoCaja()) {
            $comprobantes = CompAfip::query()
                ->where('idMovimientos', $this->idMovimientos)
                ->orderByDesc('id')
                ->get();
            $facturasAnulables = $svc->facturasAnulablesCaja($this->idMovimientos);
        } else {
            $comprobantes = CompAfip::query()
                ->where('idPacientes', (string) $this->idPacientes)
                ->orderByDesc('id')
                ->get();
            $facturasAnulables = $svc->facturasAnulables($this->idPacientes);
        }

        $emisorOk = FacturacionAfipConfig::emisorPuedeFacturar(labCtx()->usuario());
        $simulando = ! empty(FacturacionAfipConfig::config()['simular']);
        $esResponsableInscripto = FacturacionAfipConfig::esResponsableInscripto();
        $docsReceptor = FacturacionAfipConfig::esModoMovimientoCaja()
            ? $this->documentosReceptorCaja()
            : null;

        $ivaPreview = null;
        $ivaPreviewError = null;
        $condicionesIva = [];
        $receptorEsConsumidorFinal = false;
        $receptorEsCuit = false;
        if ($esResponsableInscripto) {
            if (is_array($docsReceptor)) {
                $identificacion = $this->identificacionCaja($docsReceptor);
            } else {
                $pacientePreview = Paciente::query()
                    ->with('cliente:idClientes,cuit,dni')
                    ->find($this->idPacientes);
                $identificacion = $pacientePreview instanceof Paciente
                    ? $this->identificacionPaciente($pacientePreview)
                    : ['es_cuit' => false, 'es_consumidor_final' => true];
            }

            $receptorEsConsumidorFinal = $identificacion['es_consumidor_final'];
            $receptorEsCuit = $identificacion['es_cuit'];
            $condicionesIva = FacturacionIva::opcionesCondicion(true, false);

            try {
                $importeOrigen = $this->importeOrigen();
                if ($importeOrigen > 0) {
                    $ivaPreview = FacturacionIva::desglosar($importeOrigen);
                    $ivaPreview['letra'] = match ($this->condicionIvaReceptorId) {
                        FacturacionIva::CONDICION_RI => 'A',
                        0 => '',
                        default => 'B',
                    };
                }
            } catch (RuntimeException $e) {
                $ivaPreviewError = $e->getMessage();
            }
        }

        return view('livewire.facturacion.comprobantes-afip-index', [
            'comprobantes' => $comprobantes,
            'facturasAnulables' => $facturasAnulables,
            'emisorOk' => $emisorOk,
            'simulando' => $simulando,
            'esModoCaja' => FacturacionAfipConfig::esModoMovimientoCaja(),
            'esModoPaciente' => FacturacionAfipConfig::esModoPaciente(),
            'docsEleccion' => FacturacionAfipConfig::esModoPaciente()
                ? $this->documentosEleccion()
                : null,
            'docsReceptor' => $docsReceptor,
            'esResponsableInscripto' => $esResponsableInscripto,
            'condicionesIva' => $condicionesIva,
            'receptorEsConsumidorFinal' => $receptorEsConsumidorFinal,
            'receptorEsCuit' => $receptorEsCuit,
            'ivaPreview' => $ivaPreview,
            'ivaPreviewError' => $ivaPreviewError,
            'precioIncluyeIva' => FacturacionAfipConfig::precioIncluyeIva(),
            'urlPdfFn' => static fn (int $id): string => route('facturacion.afip.comprobante.pdf', [
                'ref' => OpaqueRouteToken::forCompAfip($id),
            ]),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
