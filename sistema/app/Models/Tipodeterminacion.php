<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tipodeterminacion extends Model
{
    protected $table = 'tipodeterminaciones';

    protected $primaryKey = 'idTipodeterminaciones';

    public $timestamps = false;

    protected $fillable = [
        'orden',
        'nombre',
        'precio',
        'precio2',
        'precio3',
        'filaDesde',
        'filasCant',
        'destino',
        'perfil',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'precio' => 'decimal:2',
            'precio2' => 'decimal:2',
            'precio3' => 'decimal:2',
            'filaDesde' => 'integer',
            'filasCant' => 'integer',
            'destino' => 'integer',
            'perfil' => 'integer',
        ];
    }

    public function renglonesPlantilla(): HasMany
    {
        return $this->hasMany(Renglonesxdeterminacion::class, 'idTipodeterminaciones', 'idTipodeterminaciones');
    }
}
