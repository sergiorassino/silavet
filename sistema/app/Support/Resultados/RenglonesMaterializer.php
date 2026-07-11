<?php

namespace App\Support\Resultados;

use App\Models\Determinacion;
use App\Models\Itemsinforme;
use App\Models\Paciente;
use App\Models\Renglon;
use App\Models\Renglonesxdeterminacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Materializa filas de `renglones` a partir de las determinaciones del protocolo
 * y la plantilla `renglonesxdeterminacion`. No inventa valores: deja valor/valor2 vacíos.
 */
class RenglonesMaterializer
{
    public function asegurarParaPaciente(Paciente $paciente): void
    {
        if (! Schema::hasTable('renglones') || ! Schema::hasTable('renglonesxdeterminacion')) {
            return;
        }

        $determinaciones = Determinacion::query()
            ->where('idPacientes', $paciente->idPacientes)
            ->get(['idTipodeterminaciones', 'idClientes']);

        foreach ($determinaciones as $determinacion) {
            $this->asegurarParaDeterminacion(
                $paciente,
                (int) $determinacion->idTipodeterminaciones,
                (int) ($determinacion->idClientes ?: $paciente->idClientes)
            );
        }
    }

    public function asegurarParaDeterminacion(Paciente $paciente, int $idTipodeterminacion, ?int $idClientes = null): void
    {
        if (! Schema::hasTable('renglones') || ! Schema::hasTable('renglonesxdeterminacion')) {
            return;
        }

        $yaExiste = Renglon::query()
            ->where('idPacientes', $paciente->idPacientes)
            ->where('idTipodeterminacion', $idTipodeterminacion)
            ->exists();

        if ($yaExiste) {
            return;
        }

        $plantilla = Renglonesxdeterminacion::query()
            ->where('idTipodeterminaciones', $idTipodeterminacion)
            ->orderBy('orden')
            ->get(['idItemsinforme', 'orden']);

        if ($plantilla->isEmpty()) {
            return;
        }

        $items = Itemsinforme::query()
            ->whereIn('idItems', $plantilla->pluck('idItemsinforme')->all())
            ->get()
            ->keyBy('idItems');

        $idClientesFinal = $idClientes ?? (int) $paciente->idClientes;
        $filas = [];

        foreach ($plantilla as $renglonPlantilla) {
            $item = $items->get((int) $renglonPlantilla->idItemsinforme);
            if ($item === null) {
                continue;
            }

            $filas[] = [
                'idClientes' => $idClientesFinal,
                'idPacientes' => (int) $paciente->idPacientes,
                'idGrupos' => (int) ($item->idGrupos ?? 0),
                'idTipodeterminacion' => $idTipodeterminacion,
                'orden' => (int) ($renglonPlantilla->orden ?? 0),
                'tipoItem' => (int) ($item->tipoItem ?? 0),
                'idItems' => (int) $item->idItems,
                'valor' => '',
                'valor2' => '',
                'tipoHtml' => null,
                'idAnalizador' => (string) ($item->idAnalizador ?? ''),
                'mostrar' => 1,
            ];
        }

        if ($filas === []) {
            return;
        }

        DB::table('renglones')->insert($filas);
    }

    public function eliminarParaDeterminacion(int $idPacientes, int $idTipodeterminacion): void
    {
        if (! Schema::hasTable('renglones')) {
            return;
        }

        Renglon::query()
            ->where('idPacientes', $idPacientes)
            ->where('idTipodeterminacion', $idTipodeterminacion)
            ->delete();
    }
}
