<?php

namespace App\Livewire\Clientes;

use App\Support\CuentaCorriente\CuentaCorrienteConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Livewire\Component;

class CuentaCorrienteIndex extends Component
{
    public string $busqueda = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
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
        $clientes = CuentaCorrienteConsulta::clientesListado($this->busqueda);
        $saldoTotal = round($clientes->sum(fn ($cliente) => (float) $cliente->saldo_total), 2);

        return view('livewire.clientes.cuenta-corriente-index', compact('clientes', 'saldoTotal'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
