<?php

namespace App\Livewire\Concerns;

use App\Models\Cliente;
use App\Models\MedioDePago;
use App\Models\Paciente;
use App\Support\Tesoreria\PagoGlobalRegistro;
use App\Support\Tesoreria\TesoreriaConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

/**
 * Modal de pago global (Pacientes y Cuenta Corriente).
 * Solo opera si {@see TesoreriaConfig::pagoGlobalHabilitado()}.
 */
trait ConModalPagoGlobal
{
    public bool $modalPagoGlobalAbierto = false;

    public ?int $pagoGlobalIdPacientes = null;

    public ?int $pagoGlobalIdClientes = null;

    public string $pagoGlobalImporte = '';

    public ?int $pagoGlobalIdMediodepago = null;

    public function abrirModalPagoGlobal(): void
    {
        $this->pagoGlobalAssertPermiso();
        abort_unless(TesoreriaConfig::pagoGlobalHabilitado(), 404);

        $this->pagoGlobalIdPacientes = null;
        $this->pagoGlobalImporte = '';
        $this->pagoGlobalIdMediodepago = null;
        $this->pagoGlobalIdClientes = $this->pagoGlobalClienteInicial();
        $this->modalPagoGlobalAbierto = true;
        $this->resetErrorBag();
    }

    public function abrirModalEditarPagoGlobal(int $id): void
    {
        $this->pagoGlobalAssertPermiso();
        abort_unless(TesoreriaConfig::pagoGlobalHabilitado(), 404);

        $paciente = $this->pagoGlobalPacienteEnAlcance($id);
        if ($paciente === null || ! $paciente->esPagoGlobal()) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el pago global.');

            return;
        }

        $this->pagoGlobalIdPacientes = (int) $paciente->idPacientes;
        $this->pagoGlobalIdClientes = (int) $paciente->idClientes;
        $this->pagoGlobalImporte = number_format((float) $paciente->pagado, 2, ',', '');
        $this->pagoGlobalIdMediodepago = (int) ($paciente->idMediodepago ?: 0) ?: null;
        $this->modalPagoGlobalAbierto = true;
        $this->resetErrorBag();
    }

    public function cerrarModalPagoGlobal(): void
    {
        $this->modalPagoGlobalAbierto = false;
        $this->pagoGlobalIdPacientes = null;
        $this->pagoGlobalImporte = '';
        $this->pagoGlobalIdMediodepago = null;
        $this->resetErrorBag();
    }

    public function guardarPagoGlobal(): void
    {
        $this->pagoGlobalAssertPermiso();
        abort_unless(TesoreriaConfig::pagoGlobalHabilitado(), 404);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'pago-global:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $this->pagoGlobalImporte = PagoGlobalRegistro::normalizarImporte($this->pagoGlobalImporte);

        $this->validate([
            'pagoGlobalIdClientes' => ['required', 'integer', 'exists:clientes,idClientes'],
            'pagoGlobalImporte' => ['required', 'numeric', 'gt:0'],
            'pagoGlobalIdMediodepago' => ['required', 'integer', 'exists:mediodepago,id'],
        ], [
            'pagoGlobalIdClientes.required' => 'Seleccione el cliente.',
            'pagoGlobalIdClientes.exists' => 'El cliente seleccionado no es válido.',
            'pagoGlobalImporte.required' => 'Ingrese el importe.',
            'pagoGlobalImporte.numeric' => 'El importe no es válido.',
            'pagoGlobalImporte.gt' => 'El importe debe ser mayor a cero.',
            'pagoGlobalIdMediodepago.required' => 'Seleccione el medio de pago.',
            'pagoGlobalIdMediodepago.exists' => 'El medio de pago seleccionado no es válido.',
        ]);

        $idClientes = (int) $this->pagoGlobalIdClientes;
        $this->pagoGlobalAssertClientePermitido($idClientes);

        RateLimiter::hit($key, 60);

        $existente = null;
        if ($this->pagoGlobalIdPacientes !== null) {
            $existente = $this->pagoGlobalPacienteEnAlcance($this->pagoGlobalIdPacientes);
            if ($existente === null || ! $existente->esPagoGlobal()) {
                $this->dispatch('vl-swal-error', mensaje: 'No se encontró el pago global.');
                $this->cerrarModalPagoGlobal();

                return;
            }
        }

        PagoGlobalRegistro::guardar($existente, [
            'idClientes' => $idClientes,
            'pagado' => round((float) $this->pagoGlobalImporte, 2),
            'idMediodepago' => (int) $this->pagoGlobalIdMediodepago,
            'fechhoy' => $this->pagoGlobalFecha(),
        ]);

        $mensaje = $existente !== null
            ? 'Pago global actualizado correctamente.'
            : 'Pago global registrado correctamente.';

        $this->cerrarModalPagoGlobal();
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);
    }

    public function pagoGlobalClienteEditable(): bool
    {
        return true;
    }

    public function pagoGlobalFechaEtiqueta(): string
    {
        return \Illuminate\Support\Carbon::parse($this->pagoGlobalFecha())->format('d/m/Y');
    }

    /**
     * @return array{clientesPagoGlobal: Collection, mediosPago: Collection, mostrarPagoGlobal: bool}
     */
    protected function datosVistaPagoGlobal(bool $mostrar = true): array
    {
        $mostrarPagoGlobal = $mostrar && TesoreriaConfig::pagoGlobalHabilitado();
        $clientesPagoGlobal = collect();
        $mediosPago = collect();

        if ($this->modalPagoGlobalAbierto && $mostrarPagoGlobal) {
            $clientesPagoGlobal = $this->pagoGlobalClientesParaSelect();

            if (Schema::hasTable('mediodepago')) {
                $mediosPago = MedioDePago::query()
                    ->orderBy('nombreMedioPago')
                    ->get(['id', 'nombreMedioPago']);
            }
        }

        return compact('clientesPagoGlobal', 'mediosPago', 'mostrarPagoGlobal');
    }

    abstract protected function pagoGlobalAssertPermiso(): void;

    protected function pagoGlobalFecha(): string
    {
        return now()->toDateString();
    }

    protected function pagoGlobalClienteInicial(): ?int
    {
        return null;
    }

    protected function pagoGlobalAssertClientePermitido(int $idClientes): void
    {
        $fijo = $this->pagoGlobalClienteInicial();
        if ($fijo !== null) {
            abort_unless($idClientes === $fijo, 403);
        }
    }

    protected function pagoGlobalPacienteEnAlcance(int $id): ?Paciente
    {
        return Paciente::query()
            ->where('idPacientes', $id)
            ->first();
    }

    /**
     * @return Collection<int, Cliente>
     */
    protected function pagoGlobalClientesParaSelect()
    {
        $fijo = $this->pagoGlobalClienteInicial();

        return Cliente::query()
            ->when($fijo !== null, fn ($q) => $q->where('idClientes', $fijo))
            ->orderBy('nombre')
            ->get(['idClientes', 'nombre']);
    }
}
