<?php

namespace App\Livewire\Cliente;

use App\Support\Dashboard\DashboardClienteConsulta;
use App\Support\PermisosIaCatalog;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Campana de avisos sin leer en la autogestión del cliente.
 */
class AvisosCampana extends Component
{
    public bool $abierto = false;

    /** default | hero (sobre fondo oscuro del encabezado) */
    public string $variant = 'default';

    public function mount(string $variant = 'default'): void
    {
        abort_unless(labCtx()->esCliente() && labCtx()->idClientes, 403);
        $this->variant = in_array($variant, ['default', 'hero'], true) ? $variant : 'default';
    }

    public function togglePanel(): void
    {
        $idClientes = (int) labCtx()->idClientes;
        $conteo = DashboardClienteConsulta::conteoAvisosNoLeidos($idClientes);

        if ($conteo < 1) {
            $this->abierto = false;

            return;
        }

        $this->abierto = ! $this->abierto;
    }

    public function cerrarPanel(): void
    {
        $this->abierto = false;
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

        if (DashboardClienteConsulta::conteoAvisosNoLeidos($idClientes) < 1) {
            $this->abierto = false;
        }

        $this->dispatch('cliente-avisos-actualizados');
    }

    #[On('cliente-avisos-actualizados')]
    public function onAvisosActualizados(): void
    {
        $idClientes = (int) labCtx()->idClientes;
        if (DashboardClienteConsulta::conteoAvisosNoLeidos($idClientes) < 1) {
            $this->abierto = false;
        }
    }

    public function render()
    {
        $idClientes = (int) labCtx()->idClientes;
        $puedeVerInformes = tienePermiso(PermisosIaCatalog::INFORMES);
        $conteo = DashboardClienteConsulta::conteoAvisosNoLeidos($idClientes);

        return view('livewire.cliente.avisos-campana', [
            'conteo' => $conteo,
            'tieneSinLeer' => $conteo > 0,
            'avisos' => $this->abierto
                ? DashboardClienteConsulta::avisosNoLeidos(
                    $idClientes,
                    $puedeVerInformes,
                    DashboardClienteConsulta::LIMITE_AVISOS_PANEL,
                )
                : [],
        ]);
    }
}
