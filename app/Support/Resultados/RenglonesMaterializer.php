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
 * y la plantilla `renglonesxdeterminacion`.
 *
 * `valor` al alta:
 * - tipoItem 2 (valor fijo) y 3 (título): vacío
 * - tipoItem 8 (texto largo): copia `itemsinforme.textos`
 * - resto: "PENDIENTE"
 *
 * `valor2` siempre vacío.
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

            $tipoItem = (int) ($item->tipoItem ?? 0);

            $filas[] = [
                'idClientes' => $idClientesFinal,
                'idPacientes' => (int) $paciente->idPacientes,
                'idGrupos' => (int) ($item->idGrupos ?? 0),
                'idTipodeterminacion' => $idTipodeterminacion,
                'orden' => (int) ($renglonPlantilla->orden ?? 0),
                'tipoItem' => $tipoItem,
                'idItems' => (int) $item->idItems,
                'valor' => $this->valorInicial($tipoItem, $item),
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

    private function valorInicial(int $tipoItem, Itemsinforme $item): string
    {
        if (in_array($tipoItem, [2, 3], true)) {
            return '';
        }

        if ($tipoItem === 8) {
            return (string) ($item->textos ?? '');
        }

        return 'PENDIENTE';
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
