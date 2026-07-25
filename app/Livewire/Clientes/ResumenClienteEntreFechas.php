<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class ResumenClienteEntreFechas extends Component
{
    public ?int $idClientes = null;

    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(Schema::hasTable('pacientes'), 404, 'La tabla de pacientes no está disponible.');

        $hoy = now();
        $this->fechaDesde = $hoy->copy()->startOfMonth()->toDateString();
        $this->fechaHasta = $hoy->toDateString();
    }

    public function updatedIdClientes(mixed $value): void
    {
        $this->idClientes = ($value === '' || $value === null) ? null : (int) $value;
    }

    public function generarPdf(): void
    {
        $this->validate([
            'idClientes' => ['required', 'integer', 'exists:clientes,idClientes'],
            'fechaDesde' => ['required', 'date'],
            'fechaHasta' => ['required', 'date', 'after_or_equal:fechaDesde'],
        ], [
            'idClientes.required' => 'Seleccione un cliente.',
            'idClientes.exists' => 'El cliente indicado no existe.',
            'fechaDesde.required' => 'Indique la fecha desde.',
            'fechaHasta.required' => 'Indique la fecha hasta.',
            'fechaDesde.date' => 'La fecha desde no es válida.',
            'fechaHasta.date' => 'La fecha hasta no es válida.',
            'fechaHasta.after_or_equal' => 'La fecha desde no puede ser posterior a la hasta.',
        ]);

        $this->dispatch('vl-abrir-url', url: route('clientes.resumen-entre-fechas.pdf', [
            'idClientes' => $this->idClientes,
            'desde' => $this->fechaDesde,
            'hasta' => $this->fechaHasta,
        ]));
    }

    public function render()
    {
        $clientes = Cliente::query()
            ->when(Schema::hasColumn('clientes', 'tipoCliente'), function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('clientes.tipoCliente')
                        ->orWhere('clientes.tipoCliente', '!=', 1);
                });
            })
            ->orderBy('nombre')
            ->get(['idClientes', 'nombre']);

        return view('livewire.clientes.resumen-cliente-entre-fechas', [
            'clientes' => $clientes,
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
