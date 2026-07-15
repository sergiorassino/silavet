<?php

namespace App\Livewire\Cliente;

use App\Support\UsuarioMenuPortal;
use Livewire\Component;

class ListaPrecios extends Component
{
    public function mount(): void
    {
        abort_unless(labCtx()->esCliente(), 403);
    }

    public function render()
    {
        $url = labListaPreciosUrl();

        return view('livewire.cliente.lista-precios', [
            'tieneLista' => $url !== null && $url !== '',
            'pdfUrl' => $url ? route('cliente.lista-precios.pdf') : null,
        ])->layout('layouts.staff', UsuarioMenuPortal::clienteLayoutParams());
    }
}
