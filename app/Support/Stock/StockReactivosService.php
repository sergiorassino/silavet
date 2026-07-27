<?php

namespace App\Support\Stock;

use App\Models\Reactivoxdeterminacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Descuenta o repone el stock de reactivos al alta/baja de una determinación.
 *
 * Permite saldo negativo: nunca bloquea el alta.
 * Devuelve los reactivos que quedaron por debajo (o en) su minAviso para que
 * el componente Livewire muestre un aviso SweetAlert.
 */
class StockReactivosService
{
    /**
     * Descuenta del stock los reactivos que consume el tipo de determinación.
     *
     * @return array<int, array{reactivo: string, cantidad: int}> Reactivos que quedaron <= minAviso.
     */
    public function descontarPorTipo(int $idTipodeterminaciones): array
    {
        if (! $this->tablaDisponible()) {
            return [];
        }

        $consumos = Reactivoxdeterminacion::query()
            ->where('idTipodeterminaciones', $idTipodeterminaciones)
            ->get();

        if ($consumos->isEmpty()) {
            return [];
        }

        $bajosMinimo = [];

        DB::transaction(function () use ($consumos, &$bajosMinimo) {
            foreach ($consumos as $consumo) {
                $reactivo = \App\Models\Reactivo::query()
                    ->lockForUpdate()
                    ->find($consumo->idReactivos);

                if ($reactivo === null) {
                    continue;
                }

                $reactivo->decrement('cantidad', (float) $consumo->cantidad);
                $reactivo->refresh();

                if ($reactivo->cantidad <= $reactivo->minAviso) {
                    $bajosMinimo[] = [
                        'reactivo' => (string) $reactivo->reactivo,
                        'cantidad' => (int) $reactivo->cantidad,
                    ];
                }
            }
        });

        return $bajosMinimo;
    }

    /**
     * Repone al stock los reactivos que consume el tipo de determinación.
     * Se llama al eliminar una determinación del protocolo.
     */
    public function reponerPorTipo(int $idTipodeterminaciones): void
    {
        if (! $this->tablaDisponible()) {
            return;
        }

        $consumos = Reactivoxdeterminacion::query()
            ->where('idTipodeterminaciones', $idTipodeterminaciones)
            ->get();

        if ($consumos->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($consumos) {
            foreach ($consumos as $consumo) {
                \App\Models\Reactivo::query()
                    ->lockForUpdate()
                    ->where('id', $consumo->idReactivos)
                    ->get(); // lock adquirido

                \App\Models\Reactivo::query()
                    ->where('id', $consumo->idReactivos)
                    ->increment('cantidad', (float) $consumo->cantidad);
            }
        });
    }

    private function tablaDisponible(): bool
    {
        return Schema::hasTable('reactivoxdeterminacion')
            && Schema::hasColumn('reactivoxdeterminacion', 'idTipodeterminaciones');
    }
}
