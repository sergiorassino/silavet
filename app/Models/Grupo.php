<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Grupo extends Model
{
    protected $table = 'grupos';

    protected $primaryKey = 'idGrupos';

    public $timestamps = false;

    protected $fillable = [
        'nombreGrupo',
        'orden',
        'mostrarReferencias',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'mostrarReferencias' => 'integer',
        ];
    }

    public static function tieneColumnaMostrarReferencias(): bool
    {
        return Schema::hasTable('grupos')
            && Schema::hasColumn('grupos', 'mostrarReferencias');
    }
}
