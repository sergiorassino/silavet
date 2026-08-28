<?php

namespace App\Livewire\Concerns;

use App\Models\MedioDePago;
use App\Models\Paciente;
use App\Support\Tesoreria\PagoGlobalRegistro;
use App\Support\Tesoreria\TesoreriaConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

/**
 * Modal de importe pagado + medio de pago en protocolos (PacienteIndex staff).
 * Solo opera si {@see TesoreriaConfig::columnaPagadoHabilitada()}.
 */
trait ConModalPagadoProtocolo
{
    public bool $modalPagadoProtocoloAbierto = false;

    public ?int $pagadoProtocoloIdPacientes = null;

    public string $pagadoProtocoloImporte = '';

    public ?int $pagadoProtocoloIdMediodepago = null;

    public function abrirModalPagadoProtocolo(int $id): void
    {
        $this->pagadoProtocoloAssertPermiso();
        abort_unless(TesoreriaConfig::columnaPagadoHabilitada(), 404);

        $paciente = $this->pagadoProtocoloPacienteGestionable($id);
        if ($paciente === null) {
            return;
        }

        $this->pagadoProtocoloIdPacientes = (int) $paciente->idPacientes;
        $this->pagadoProtocoloImporte = number_format((float) ($paciente->pagado ?? 0), 2, ',', '');
        $this->pagadoProtocoloIdMediodepago = (int) ($paciente->idMediodepago ?: 0) ?: null;
        $this->modalPagadoProtocoloAbierto = true;
        $this->resetErrorBag();
    }

    public function cerrarModalPagadoProtocolo(): void
    {
        $this->modalPagadoProtocoloAbierto = false;
        $this->pagadoProtocoloIdPacientes = null;
        $this->pagadoProtocoloImporte = '';
        $this->pagadoProtocoloIdMediodepago = null;
        $this->resetErrorBag();
    }

    public function guardarPagadoProtocolo(): void
    {
        $this->pagadoProtocoloAssertPermiso();
        abort_unless(TesoreriaConfig::columnaPagadoHabilitada(), 404);

        if (! Schema::hasColumn('pacientes', 'pagado')) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede guardar el pago: falta la columna pacientes.pagado en este laboratorio.'
            );

            return;
        }

        if (! Schema::hasColumn('pacientes', 'idMediodepago')) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'No se puede guardar el medio de pago: falta la columna pacientes.idMediodepago en este laboratorio.'
            );

            return;
        }

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'protocolos-pagado:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        if ($this->pagadoProtocoloIdPacientes === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el protocolo.');

            return;
        }

        $paciente = $this->pagadoProtocoloPacienteGestionable($this->pagadoProtocoloIdPacientes);
        if ($paciente === null) {
            $this->cerrarModalPagadoProtocolo();

            return;
        }

        $this->pagadoProtocoloImporte = PagoGlobalRegistro::normalizarImporte($this->pagadoProtocoloImporte);

        $importe = round((float) $this->pagadoProtocoloImporte, 2);

        $reglas = [
            'pagadoProtocoloImporte' => ['required', 'numeric', 'min:0'],
        ];
        $mensajes = [
            'pagadoProtocoloImporte.required' => 'Ingrese el importe pagado.',
            'pagadoProtocoloImporte.numeric' => 'El importe pagado no es válido.',
            'pagadoProtocoloImporte.min' => 'El importe pagado no puede ser negativo.',
        ];

        if ($importe > 0) {
            $reglas['pagadoProtocoloIdMediodepago'] = ['required', 'integer', 'exists:mediodepago,id'];
            $mensajes['pagadoProtocoloIdMediodepago.required'] = 'Seleccione el medio de pago.';
            $mensajes['pagadoProtocoloIdMediodepago.exists'] = 'El medio de pago seleccionado no es válido.';
        } else {
            $reglas['pagadoProtocoloIdMediodepago'] = ['nullable', 'integer', 'exists:mediodepago,id'];
        }

        $this->validate($reglas, $mensajes);

        RateLimiter::hit($key, 60);

        $payload = [
            'pagado' => $importe,
            'idMediodepago' => $importe > 0 ? (int) $this->pagadoProtocoloIdMediodepago : null,
        ];

        $paciente->update($payload);

        $this->cerrarModalPagadoProtocolo();
    }

    /**
     * @return array{mediosPagoPagadoProtocolo: Collection}
     */
    protected function datosVistaPagadoProtocolo(bool $mostrar = true): array
    {
        $mediosPagoPagadoProtocolo = collect();

        if ($mostrar
            && TesoreriaConfig::columnaPagadoHabilitada()
            && $this->modalPagadoProtocoloAbierto
            && Schema::hasTable('mediodepago')
        ) {
            $mediosPagoPagadoProtocolo = MedioDePago::query()
                ->orderBy('nombreMedioPago')
                ->get(['id', 'nombreMedioPago']);
        }

        return compact('mediosPagoPagadoProtocolo');
    }

    abstract protected function pagadoProtocoloAssertPermiso(): void;

    abstract protected function pagadoProtocoloPacienteGestionable(int $id): ?Paciente;
}
