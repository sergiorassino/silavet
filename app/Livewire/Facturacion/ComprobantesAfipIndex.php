<?php

namespace App\Livewire\Facturacion;

use App\Models\CompAfip;
use App\Models\Paciente;
use App\Support\Facturacion\FacturacionAfipConfig;
use App\Support\Facturacion\FacturacionAfipService;
use App\Support\PermisosIaCatalog;
use App\Support\Protocolos\PacienteListadoFiltros;
use App\Support\Security\OpaqueRouteToken;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use RuntimeException;

class ComprobantesAfipIndex extends Component
{
    public int $idPacientes = 0;

    public string $origenLabel = '';

    public string $clienteLabel = '';

    public string $importeLabel = '';

    public string $volverUrl = '';

    public ?int $idFacturaNc = null;

    public function mount(string $ref): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(FacturacionAfipConfig::habilitada(), 403);

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
            $this->volverUrl = route('tesoreria.movimientos.index');
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

        $importe = $tipo === Paciente::TIPO_INGRESO
            ? (float) $paciente->pagado
            : (float) $paciente->precio;
        $this->importeLabel = '$ '.number_format(abs($importe), 2, ',', '.');
    }

    public function emitirFactura(): void
    {
        $this->ejecutarEmision(fn (FacturacionAfipService $svc) => $svc->emitirFactura($this->idPacientes));
    }

    public function emitirComanda(): void
    {
        $this->ejecutarEmision(fn (FacturacionAfipService $svc) => $svc->emitirComanda($this->idPacientes));
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

    public function render()
    {
        $comprobantes = CompAfip::query()
            ->where('idPacientes', (string) $this->idPacientes)
            ->orderByDesc('id')
            ->get();

        $svc = app(FacturacionAfipService::class);
        $facturasAnulables = $svc->facturasAnulables($this->idPacientes);
        $emisorOk = FacturacionAfipConfig::emisorPuedeFacturar(labCtx()->usuario());
        $simulando = ! empty(FacturacionAfipConfig::config()['simular']);

        return view('livewire.facturacion.comprobantes-afip-index', [
            'comprobantes' => $comprobantes,
            'facturasAnulables' => $facturasAnulables,
            'emisorOk' => $emisorOk,
            'simulando' => $simulando,
            'urlPdfFn' => static fn (int $id): string => route('facturacion.afip.comprobante.pdf', [
                'ref' => OpaqueRouteToken::forCompAfip($id),
            ]),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
