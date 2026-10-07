<?php

namespace App\Livewire\Cliente;

use App\Models\Entorno;
use App\Support\Cliente\PortalClienteConfig;
use App\Support\Entorno\EntornoArchivos;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class ListaPrecios extends Component
{
    public function mount(): void
    {
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
     * URL nueva en cada apertura. El archivo en disco se llama siempre
     * lista-precios.pdf; sin un query distinto el navegador muestra el PDF viejo.
     */
    private function urlPdfSinCache(): ?string
    {
        if (labListaPreciosUrl() === null) {
            return null;
        }

        $marca = (string) hrtime(true);
        if (Schema::hasTable('entorno')) {
            $entorno = Entorno::query()->find(1);
            $abs = EntornoArchivos::rutaAbsoluta($entorno?->listaPreciosPdf ?? null);
            if ($abs !== null && is_file($abs)) {
                $marca = ((int) filemtime($abs)).'-'.((int) filesize($abs)).'-'.$marca;
            }
        }

        return route('cliente.lista-precios.pdf', ['v' => $marca]);
    }
}
