<?php

namespace App\Livewire;

use App\Support\Dashboard\DashboardLabConsulta;
use App\Support\UsuarioMenuPortal;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard', [
            'metricas' => DashboardLabConsulta::metricas(),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
