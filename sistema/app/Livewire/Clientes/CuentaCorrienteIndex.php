<?php

namespace App\Livewire\Clientes;

use App\Support\CuentaCorriente\CuentaCorrienteConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Livewire\Component;
use Livewire\WithPagination;

class CuentaCorrienteIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $busqueda = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
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
        $clientes = CuentaCorrienteConsulta::clientesPaginados($this->busqueda, self::POR_PAGINA);

        return view('livewire.clientes.cuenta-corriente-index', compact('clientes'))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
