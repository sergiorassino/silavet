<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reactivo extends Model
{
    protected $table = 'reactivos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'reactivo',
        'cantidad',
        'minAviso',
        'existIdeal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad'   => 'integer',
            'minAviso'   => 'integer',
            'existIdeal' => 'integer',
        ];
    }

    public function consumos(): HasMany
    {
        return $this->hasMany(Reactivoxdeterminacion::class, 'idReactivos', 'id');
    }

    public function estaBajoMinimo(): bool
    {
        return $this->cantidad <= $this->minAviso;
    }
}
