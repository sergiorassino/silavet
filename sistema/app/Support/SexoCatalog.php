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
}
