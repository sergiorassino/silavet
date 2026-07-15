<?php

namespace App\Livewire\Cliente;

use App\Support\UsuarioMenuPortal;
use Livewire\Component;

class ClienteHome extends Component
{
    public function render()
    {
        $ctx = labCtx();

        return view('livewire.cliente.home', [
            'nombreUsuario' => $ctx->usuario()?->apenom ?? 'usuario',
            'nombreCliente' => $ctx->usuario()?->cliente?->nombre ?? '',
        ])->layout('layouts.staff', UsuarioMenuPortal::clienteLayoutParams());
    }
}
