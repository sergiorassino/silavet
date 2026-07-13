<?php

namespace App\Support\Resultados;

use App\Models\Paciente;
use App\Models\Renglon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Lista todos los renglones del informe (visibles y ocultos) para Ed.Inf.
 */
class InformeVisibilidadConsulta
{
    /**
     * @return list<array{
     *     idRenglones: int,
     *     idGrupos: int,
     *     nombreGrupo: string,
     *     nombreItem: string,
     *     orden: int,
     *     mostrar: int
     * }>
     */
    public function listar(Paciente $paciente): array
    {
        if (! Schema::hasTable('renglones')) {
            return [];
        }

        $query = Renglon::query()
            ->from('renglones as r')
            ->join('itemsinforme as i', 'r.idItems', '=', 'i.idItems')
            ->join('grupos as g', 'r.idGrupos', '=', 'g.idGrupos')
            ->leftJoin('tipodeterminaciones as t', 'r.idTipodeterminacion', '=', 't.idTipodeterminaciones')
            ->where('r.idPacientes', $paciente->idPacientes)
            ->select([
                'r.idRenglones',
                'r.idGrupos',
                'r.orden',
                'r.mostrar',
                'i.nombreItem',
                'g.nombreGrupo',
                'g.orden as ordenGrupo',
            ])
            ->orderBy('g.orden')
            ->orderBy('g.idGrupos')
            ->orderBy('t.orden');

        if (Schema::hasColumn('renglones', 'duplic')) {
            $query->orderBy('r.duplic');
        }

        /** @var Collection<int, object> $filas */
        $filas = $query
            ->orderBy('r.orden')
            ->orderBy('r.idRenglones')
            ->get();

        return $filas->map(static fn (object $fila): array => [
            'idRenglones' => (int) $fila->idRenglones,
            'idGrupos' => (int) $fila->idGrupos,
            'nombreGrupo' => (string) $fila->nombreGrupo,
            'nombreItem' => (string) $fila->nombreItem,
            'orden' => (int) $fila->orden,
            'mostrar' => (int) $fila->mostrar === 1 ? 1 : 0,
        ])->all();
    }
}
