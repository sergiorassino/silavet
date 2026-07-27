<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reactivoxdeterminacion extends Model
{
    protected $table = 'reactivoxdeterminacion';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'idTipodeterminaciones',
        'idReactivos',
        'cantidad',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:4',
        ];
    }

    public function tipodeterminacion(): BelongsTo
    {
        return $this->belongsTo(Tipodeterminacion::class, 'idTipodeterminaciones', 'idTipodeterminaciones');
    }

    public function reactivo(): BelongsTo
    {
        return $this->belongsTo(Reactivo::class, 'idReactivos', 'id');
    }
}
