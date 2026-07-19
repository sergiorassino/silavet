<?php

namespace App\Livewire\Listados;

use App\Support\Listados\ExcelPacientesConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class ExcelPacientes extends Component
{
    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);
        abort_unless(Schema::hasTable('pacientes'), 404, 'La tabla de pacientes no está disponible.');

        $hoy = now()->toDateString();
        $this->fechaDesde = $hoy;
        $this->fechaHasta = $hoy;
    }

    public function generarExcel()
    {
        $this->validate([
            'fechaDesde' => ['required', 'date'],
            'fechaHasta' => ['required', 'date', 'after_or_equal:fechaDesde'],
        ], [
            'fechaDesde.required' => 'Indique la fecha inicial.',
            'fechaHasta.required' => 'Indique la fecha final.',
            'fechaDesde.date' => 'La fecha inicial no es válida.',
            'fechaHasta.date' => 'La fecha final no es válida.',
            'fechaHasta.after_or_equal' => 'La fecha inicial no puede ser posterior a la final.',
        ]);

        return redirect()->route('listados.excel-pacientes.excel', [
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
        ]);
    }

    public function render()
    {
        return view('livewire.listados.excel-pacientes', [
            'periodoTexto' => ExcelPacientesConsulta::etiquetaPeriodo(
                $this->fechaDesde,
                $this->fechaHasta,
            ),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
