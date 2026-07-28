<?php

namespace App\Support\Resultados;

use App\Models\Paciente;
use App\Support\SexoCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payload para el JS de automatización Serie Roja / Serie Blanca.
 */
final class HemogramaAutoPayload
{
    /**
     * @return array{
     *     activo: bool,
     *     idEspecies?: int,
     *     idSexos?: int,
     *     items?: array<string, int>,
     *     idItemsDisparo?: list<int>,
     *     rangos?: list<array{idItems: int, idEspecies: int, idSexos: int, valorMin: float, valorMax: float}>
     * }
     */
    public static function paraPaciente(Paciente $paciente): array
    {
        if (! HemogramaAutoConfig::activo()) {
            return ['activo' => false];
        }

        $items = HemogramaAutoConfig::items();
        if ($items === [] || ! isset($items['serie_roja'], $items['serie_blanca'])) {
            return ['activo' => false];
        }

        return [
            'activo' => true,
            'idEspecies' => (int) ($paciente->idEspecies ?? 0),
            'idSexos' => SexoCatalog::idSexos($paciente->sexo ?? null),
            'items' => $items,
            'idItemsDisparo' => HemogramaAutoConfig::idItemsDisparo(),
            'rangos' => self::rangos(),
        ];
    }

    /**
     * @return list<array{idItems: int, idEspecies: int, idSexos: int, valorMin: float, valorMax: float}>
     */
    private static function rangos(): array
    {
        if (! Schema::hasTable('rangovalores')) {
            return [];
        }

        $cols = ['idItems', 'idEspecies', 'idSexos', 'valorMin', 'valorMax'];
        foreach ($cols as $col) {
            if (! Schema::hasColumn('rangovalores', $col)) {
                return [];
            }
        }

        return DB::table('rangovalores')
            ->select($cols)
            ->get()
            ->map(static fn ($fila): array => [
                'idItems' => (int) $fila->idItems,
                'idEspecies' => (int) $fila->idEspecies,
                'idSexos' => (int) $fila->idSexos,
                'valorMin' => (float) $fila->valorMin,
                'valorMax' => (float) $fila->valorMax,
            ])
            ->all();
    }
}
