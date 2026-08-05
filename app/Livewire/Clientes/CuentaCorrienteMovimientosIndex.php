<?php

namespace App\Livewire\Clientes;

use App\Support\CuentaCorriente\CuentaCorrienteMovimientosConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\Tesoreria\TesoreriaConfig;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class CuentaCorrienteMovimientosIndex extends Component
{
    public string $busqueda = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(TesoreriaConfig::usaPacientes() && Schema::hasTable('movimientos'), 404);
    }

    public function getPdfUrlProperty(): string
    {
        return route('clientes.cuenta-corriente.pdf', array_filter([
            'busqueda' => trim($this->busqueda) !== '' ? trim($this->busqueda) : null,
        ]));
    }

    public function getExcelUrlProperty(): string
    {
        return route('clientes.cuenta-corriente.excel', array_filter([
            'busqueda' => trim($this->busqueda) !== '' ? trim($this->busqueda) : null,
        ]));
    }

    public function render()
    {
        $clientes = CuentaCorrienteMovimientosConsulta::clientesListado($this->busqueda);
        $saldoTotal = round($clientes->sum(fn ($c) => (float) $c->saldo_total), 2);

        return view('livewire.clientes.cuenta-corriente-movimientos-index', compact('clientes', 'saldoTotal'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
