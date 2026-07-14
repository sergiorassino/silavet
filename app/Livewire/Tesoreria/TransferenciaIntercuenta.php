<?php

namespace App\Livewire\Tesoreria;

use App\Models\MedioDePago;
use App\Models\Paciente;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class TransferenciaIntercuenta extends Component
{
    public string $fecha = '';

    public string $hora = '';

    public ?int $idMediodepagoOrigen = null;

    public ?int $idMediodepagoDestino = null;

    public string $monto = '';

    public string $observaciones = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        $this->reiniciarFechaHora();
    }

    public function aceptar(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'tesoreria-transferencias:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $this->monto = $this->normalizarImporte($this->monto);
        $this->hora = $this->normalizarHora($this->hora);

        $this->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'idMediodepagoOrigen' => ['required', 'integer', 'exists:mediodepago,id'],
            'idMediodepagoDestino' => [
                'required',
                'integer',
                'exists:mediodepago,id',
                'different:idMediodepagoOrigen',
            ],
            'monto' => ['required', 'numeric', 'gt:0'],
            'observaciones' => ['nullable', 'string', 'max:65535'],
        ], [
            'fecha.required' => 'Ingrese la fecha.',
            'fecha.date_format' => 'La fecha no es válida.',
            'hora.required' => 'Ingrese la hora.',
            'idMediodepagoOrigen.required' => 'Seleccione la cuenta de origen.',
            'idMediodepagoOrigen.exists' => 'La cuenta de origen no es válida.',
            'idMediodepagoDestino.required' => 'Seleccione la cuenta de destino.',
            'idMediodepagoDestino.exists' => 'La cuenta de destino no es válida.',
            'idMediodepagoDestino.different' => 'La cuenta de destino debe ser distinta a la de origen.',
            'monto.required' => 'Ingrese el monto.',
            'monto.numeric' => 'El monto no es válido.',
            'monto.gt' => 'El monto debe ser mayor a cero.',
        ]);

        RateLimiter::hit($key, 60);

        $origen = MedioDePago::query()->whereKey((int) $this->idMediodepagoOrigen)->first();
        $destino = MedioDePago::query()->whereKey((int) $this->idMediodepagoDestino)->first();
        if ($origen === null || $destino === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontraron las cuentas seleccionadas.');

            return;
        }

        $importe = round((float) $this->monto, 2);
        $fechhoy = $this->fecha.' '.$this->hora;
        $obsUsuario = trim($this->observaciones);
        $obsBase = sprintf(
            'Transferencia intercuenta: %s → %s',
            (string) $origen->nombreMedioPago,
            (string) $destino->nombreMedioPago
        );
        $observaciones = $obsUsuario !== '' ? $obsBase.' | '.$obsUsuario : $obsBase;

        $base = [
            'fechhoy' => $fechhoy,
            'idClientes' => Paciente::ID_CLIENTES_EGRESO,
            'idCuentasdetalle' => 0,
            'pagado' => $importe,
            'observaciones' => $observaciones,
            'precio' => 0,
            'descuento' => 0,
            'saldo' => 0,
        ];

        DB::transaction(function () use ($base) {
            // Retiro de la cuenta de origen.
            Paciente::create(array_merge($base, [
                'tipoRegistro' => Paciente::TIPO_EGRESO,
                'idMediodepago' => (int) $this->idMediodepagoOrigen,
                'estado' => 'Egreso',
            ]));

            // Depósito en la cuenta de destino.
            Paciente::create(array_merge($base, [
                'tipoRegistro' => Paciente::TIPO_INGRESO,
                'idMediodepago' => (int) $this->idMediodepagoDestino,
                'estado' => 'Pago',
            ]));
        });

        $this->resetFormulario();
        $this->dispatch('vl-swal-exito', mensaje: 'Transferencia registrada correctamente.');
    }

    public function render()
    {
        $mediosPago = Schema::hasTable('mediodepago')
            ? MedioDePago::query()->orderBy('nombreMedioPago')->get(['id', 'nombreMedioPago'])
            : collect();

        return view('livewire.tesoreria.transferencia-intercuenta', compact('mediosPago'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function resetFormulario(): void
    {
        $this->idMediodepagoOrigen = null;
        $this->idMediodepagoDestino = null;
        $this->monto = '';
        $this->observaciones = '';
        $this->reiniciarFechaHora();
        $this->resetErrorBag();
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
