<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SexoCatalog
{
    /** @return Collection<int, string> */
    public static function opciones(): Collection
    {
        if (Schema::hasTable('sexos')) {
            $columna = Schema::hasColumn('sexos', 'nombre') ? 'nombre' : 'sexo';

            return DB::table('sexos')
                ->orderBy($columna)
                ->pluck($columna, $columna);
        }

        return collect([
            'Macho',
            'Hembra',
            'Macho Castrado',
            'Hembra Castrada',
            'Indeterminado',
        ]);
    }

    /**
     * idSexos para cruzar con rangovalores (misma convención que ScriptCase).
     * Preferencia: tabla sexos si existe; si no, mapa fijo por nombre.
     */
    /**
     * Lista de sexos con su id numérico para formularios de rangovalores.
     *
     * @return list<array{id: int, nombre: string}>
     */
    public static function opcionesConId(): array
    {
        if (Schema::hasTable('sexos') && Schema::hasColumn('sexos', 'idSexos')) {
            $columna = Schema::hasColumn('sexos', 'nombre') ? 'nombre' : 'sexo';

            return DB::table('sexos')
                ->orderBy($columna)
                ->get(['idSexos', $columna])
                ->map(static fn ($r): array => [
                    'id' => (int) $r->idSexos,
                    'nombre' => (string) ($r->nombre ?? $r->sexo ?? ''),
                ])
                ->all();
        }

        return [
            ['id' => 1, 'nombre' => 'Macho'],
            ['id' => 2, 'nombre' => 'Macho Castrado'],
            ['id' => 3, 'nombre' => 'Hembra'],
            ['id' => 4, 'nombre' => 'Hembra Castrada'],
        ];
    }

    /**
     * Nombre legible de un idSexos; devuelve '—' si no se encuentra.
     */
    public static function nombrePorId(int $idSexos): string
    {
        foreach (self::opcionesConId() as $opcion) {
            if ($opcion['id'] === $idSexos) {
                return $opcion['nombre'];
            }
        }

        return '—';
    }

    public static function idSexos(?string $nombre): int
    {
        $nombre = trim((string) $nombre);
        if ($nombre === '') {
            return 0;
        }

        if (Schema::hasTable('sexos') && Schema::hasColumn('sexos', 'idSexos')) {
            $columna = Schema::hasColumn('sexos', 'nombre') ? 'nombre' : 'sexo';
            if (Schema::hasColumn('sexos', $columna)) {
                $id = DB::table('sexos')->where($columna, $nombre)->value('idSexos');
                if ($id !== null) {
                    return (int) $id;
                }
            }
        }

        return match ($nombre) {
            'Macho' => 1,
            'Macho Castrado' => 2,
            'Hembra' => 3,
            'Hembra Castrada' => 4,
            default => 0,
        };
    }
}
