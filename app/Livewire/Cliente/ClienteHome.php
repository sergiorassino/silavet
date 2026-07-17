<?php

namespace App\Livewire\Cliente;

use App\Support\Dashboard\DashboardClienteConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Component;

class ClienteHome extends Component
{
    public function mount(): void
    {
        abort_unless(labCtx()->esCliente() && labCtx()->idClientes, 403);
    }

    public function marcarAvisoLeido(int $idNotificacion): void
    {
        $idClientes = (int) labCtx()->idClientes;
        $uid = (int) (labCtx()->idUsuarios ?? 0);
        $key = 'cliente-aviso-leido:'.$uid;

        if (RateLimiter::tooManyAttempts($key, 60)) {
            $this->dispatch('vl-swal-error', mensaje: 'Demasiados intentos. Esperá un momento.');

            return;
        }

        RateLimiter::hit($key, 60);

        if (! DashboardClienteConsulta::marcarAvisoLeido($idClientes, $idNotificacion)) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el aviso.');

            return;
        }

        $this->dispatch('cliente-avisos-actualizados');
    }

    public function marcarTodosAvisosLeidos(): void
    {
        $idClientes = (int) labCtx()->idClientes;
        $uid = (int) (labCtx()->idUsuarios ?? 0);
        $key = 'cliente-avisos-todos:'.$uid;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->dispatch('vl-swal-error', mensaje: 'Demasiados intentos. Esperá un momento.');

            return;
        }

        RateLimiter::hit($key, 60);

        $cantidad = DashboardClienteConsulta::marcarTodosAvisosLeidos($idClientes);

        if ($cantidad > 0) {
            $this->dispatch('vl-swal-exito', mensaje: 'Avisos marcados como leídos.');
            $this->dispatch('cliente-avisos-actualizados');
        }
    }

    #[On('cliente-avisos-actualizados')]
    public function onAvisosActualizados(): void
    {
        // Re-render: metricas() se recalcula en render().
    }

    public function render()
    {
        $ctx = labCtx();
        $idClientes = (int) $ctx->idClientes;
        $puedeVerInformes = tienePermiso(PermisosIaCatalog::INFORMES);

        return view('livewire.cliente.home', [
            'nombreUsuario' => $ctx->usuario()?->apenom ?? 'usuario',
            'nombreCliente' => $ctx->usuario()?->cliente?->nombre ?? '',
            'metricas' => DashboardClienteConsulta::metricas($idClientes, $puedeVerInformes),
        ])->layout('layouts.staff', UsuarioMenuPortal::clienteLayoutParams());
    }
}
