<?php

namespace App\Livewire\Cliente;

use App\Support\Cliente\PortalClienteConfig;
use App\Support\UsuarioMenuPortal;
use Livewire\Component;

class ListaPrecios extends Component
{
    public function mount(string $marca = ''): void
    {
        unset($marca);

        abort_unless(labCtx()->esCliente(), 403);
        abort_unless(PortalClienteConfig::mostrarListaPrecios(), 404);
    }

    public function render()
    {
        $pdfUrl = $this->urlPdfSinCache();

        return view('livewire.cliente.lista-precios', [
            'tieneLista' => $pdfUrl !== null,
            'pdfUrl' => $pdfUrl,
        ])->layout('layouts.staff', UsuarioMenuPortal::clienteLayoutParams());
    }

    /**
     * El token va en el path. Chrome reutiliza el PDF anterior si solo cambia ?v=.
     */
    private function urlPdfSinCache(): ?string
    {
        if (labListaPreciosUrl() === null) {
            return null;
        }

        return route('cliente.lista-precios.pdf', [
            'marca' => labListaPreciosMarca().'-'.hrtime(true),
        ]);
    }
}
