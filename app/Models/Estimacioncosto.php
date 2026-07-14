<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo legacy de la tabla estimacioncostos.
 *
 * En la versión nueva no se lee ni escribe: se conserva solo por
 * compatibilidad con el sistema anterior (carrito temporal de estimaciones).
 */
class Estimacioncosto extends Model
{
    protected $table = 'estimacioncostos';

    protected $primaryKey = 'idEstimacioncostos';

    public $timestamps = false;

    protected $fillable = [
        'idClientes',
        'idTipodeterminaciones',
        'precio',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'idClientes', 'idClientes');
    }

    public function tipodeterminacion(): BelongsTo
    {
        return $this->belongsTo(Tipodeterminacion::class, 'idTipodeterminaciones', 'idTipodeterminaciones');
    }
}
