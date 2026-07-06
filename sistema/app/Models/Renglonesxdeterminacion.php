<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Renglonesxdeterminacion extends Model
{
    protected $table = 'renglonesxdeterminacion';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'idTipodeterminaciones',
        'idItemsinforme',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'idTipodeterminaciones' => 'integer',
            'idItemsinforme' => 'integer',
            'orden' => 'integer',
        ];
    }

    public function tipodeterminacion(): BelongsTo
    {
        return $this->belongsTo(Tipodeterminacion::class, 'idTipodeterminaciones', 'idTipodeterminaciones');
    }

    public function itemsinforme(): BelongsTo
    {
        return $this->belongsTo(Itemsinforme::class, 'idItemsinforme', 'idItems');
    }
}
