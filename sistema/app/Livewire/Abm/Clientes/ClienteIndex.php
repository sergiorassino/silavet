<?php

namespace App\Livewire\Abm\Clientes;

use App\Models\Cliente;
use App\Support\PermisosIaCatalog;
use Livewire\Component;
use Livewire\WithPagination;

class ClienteIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public string $busqueda = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::CLIENTES), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $term = trim($this->busqueda);

        $clientes = Cliente::query()
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('nombre', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('cuit', 'like', "%{$term}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(self::POR_PAGINA);

        return view('livewire.abm.clientes.cliente-index', compact('clientes'))
            ->layout('layouts.laboratorio');
    }
}
