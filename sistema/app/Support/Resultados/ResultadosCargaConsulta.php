<?php

namespace App\Support\Resultados;

use App\Models\Paciente;
use App\Models\Renglon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Carga agrupada de renglones visibles para el formulario de resultados.
 * Una sola consulta (join), ordenada por grupo y tipodeterminación.
 */
class ResultadosCargaConsulta
{
    /**
     * @return list<array{idGrupos: int, nombreGrupo: string, ordenGrupo: int, renglones: list<array<string, mixed>>}>
     */
    public function gruposConRenglones(Paciente $paciente): array
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
            ->where('r.mostrar', 1)
            ->select([
                'r.idRenglones',
                'r.idGrupos',
                'r.idTipodeterminacion',
                'r.orden',
                'r.tipoItem',
                'r.idItems',
                'r.valor',
                'r.valor2',
                'i.nombreItem',
                'i.textos',
                'i.estiloNum',
                'i.actualiza',
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

        $grupos = [];
        $indicePorGrupo = [];

        foreach ($filas as $fila) {
            $idGrupos = (int) $fila->idGrupos;

            if (! isset($indicePorGrupo[$idGrupos])) {
                $indicePorGrupo[$idGrupos] = count($grupos);
                $grupos[] = [
                    'idGrupos' => $idGrupos,
                    'nombreGrupo' => (string) $fila->nombreGrupo,
                    'ordenGrupo' => (int) $fila->ordenGrupo,
                    'renglones' => [],
                ];
            }

            $idx = $indicePorGrupo[$idGrupos];
            $tipoItem = (int) $fila->tipoItem;

            // Valor fijo (2): no se muestra en carga (igual que el sistema anterior).
            if ($tipoItem === 2) {
                continue;
            }

            $grupos[$idx]['renglones'][] = [
                'idRenglones' => (int) $fila->idRenglones,
                'idItems' => (int) $fila->idItems,
                'nombreItem' => (string) $fila->nombreItem,
                'valor' => (string) ($fila->valor ?? ''),
                'valor2' => (string) ($fila->valor2 ?? ''),
                'tipoItem' => $tipoItem,
                'textos' => (string) ($fila->textos ?? ''),
                'estiloNum' => (int) ($fila->estiloNum ?? 0),
                'actualiza' => (int) ($fila->actualiza ?? 0),
                'opciones' => $this->opcionesSelect((string) ($fila->textos ?? '')),
                'placeholder' => $this->placeholderEstiloNum((int) ($fila->estiloNum ?? 0)),
            ];
        }

        return array_values(array_filter(
            $grupos,
            static fn (array $grupo): bool => $grupo['renglones'] !== []
        ));
    }

    /** @return list<string> */
    private function opcionesSelect(string $textos): array
    {
        if ($textos === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode('#', $textos)),
            static fn (string $opcion): bool => $opcion !== ''
        ));
    }

    private function placeholderEstiloNum(int $estiloNum): string
    {
        return match ($estiloNum) {
            1 => '0',
            2 => '0.0',
            3 => '0.00',
            4 => 'Texto',
            default => '',
        };
    }
}
