<?php

namespace App\Livewire\Tesoreria;

use App\Models\MedioDePago;
use App\Models\Movimiento;
use App\Models\TipoMovimiento;
use App\Support\PermisosIaCatalog;
use App\Support\Tesoreria\TesoreriaConfig;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Transferencia entre cuentas → 2 filas en `movimientos`
 * (variante tesoreria_pacientes / labvetciudad).
 */
class MovimientosEntreCuentas extends Component
{
    public string $fecha = '';

    public string $hora = '';

    public ?int $idCuentaOrigen = null;

    public ?int $idCuentaDestino = null;

    public string $monto = '';

    public string $observaciones = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaPacientes(), 404);
        abort_unless(Schema::hasTable('movimientos'), 404);

        $this->reiniciarFechaHora();
    }

    public function aceptar(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'tesoreria-movimientos-entre-cuentas:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $this->monto = $this->normalizarImporte($this->monto);
        $this->hora = $this->normalizarHora($this->hora);

        $this->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'idCuentaOrigen' => ['required', 'integer', Rule::exists('mediodepago', 'id')],
            'idCuentaDestino' => [
                'required',
                'integer',
                Rule::exists('mediodepago', 'id'),
                'different:idCuentaOrigen',
            ],
            'monto' => ['required', 'numeric', 'gt:0'],
            'observaciones' => ['nullable', 'string', 'max:65535'],
        ], [
            'fecha.required' => 'Ingrese la fecha.',
            'fecha.date_format' => 'La fecha no es válida.',
            'hora.required' => 'Ingrese la hora.',
            'idCuentaOrigen.required' => 'Seleccione la cuenta de origen.',
            'idCuentaOrigen.exists' => 'La cuenta de origen no es válida.',
            'idCuentaDestino.required' => 'Seleccione la cuenta de destino.',
            'idCuentaDestino.exists' => 'La cuenta de destino no es válida.',
            'idCuentaDestino.different' => 'La cuenta de destino debe ser distinta a la de origen.',
            'monto.required' => 'Ingrese el monto.',
            'monto.numeric' => 'El monto no es válido.',
            'monto.gt' => 'El monto debe ser mayor a cero.',
        ]);

        RateLimiter::hit($key, 60);

        $importe = abs(round((float) $this->monto, 2));
        $fechhora = $this->fecha.' '.$this->hora;
        $obs = trim($this->observaciones);

        $base = [
            'idClientes' => 0,
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
                'idCuentas' => (int) $this->idCuentaOrigen,
                'idTipoMovimiento' => TipoMovimiento::EGRESO,
                'monto' => $importe * -1,
            ]));

            // Depósito en la cuenta de destino (ingreso, monto positivo).
            Movimiento::create(array_merge($base, [
                'idCuentas' => (int) $this->idCuentaDestino,
                'idTipoMovimiento' => TipoMovimiento::INGRESO,
                'monto' => $importe,
            ]));
        });

        $this->resetFormulario();
        $this->dispatch('vl-swal-exito', mensaje: 'Movimiento entre cuentas registrado correctamente.');
    }

    public function salir(): void
    {
        $this->redirectRoute('tesoreria.movimientos.index', navigate: false);
    }

    public function render()
    {
        $cuentas = Schema::hasTable('mediodepago')
            ? MedioDePago::query()->orderBy('orden')->orderBy('nombreMedioPago')->get(['id', 'nombreMedioPago'])
            : collect();

        return view('livewire.tesoreria.movimientos-entre-cuentas', compact('cuentas'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function resetFormulario(): void
    {
        $this->idCuentaOrigen = null;
        $this->idCuentaDestino = null;
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
