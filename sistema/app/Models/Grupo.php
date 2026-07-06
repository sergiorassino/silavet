<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table = 'grupos';

    protected $primaryKey = 'idGrupos';

    public $timestamps = false;

    protected $fillable = [
        'nombreGrupo',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
        ];
    }
}
