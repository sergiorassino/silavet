<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rangovalor extends Model
{
    protected $table = 'rangovalores';

    protected $primaryKey = 'idRangovalores';

    public $timestamps = false;

    protected $fillable = [
        'idItems',
        'idEspecies',
        'idSexos',
        'valorMin',
        'valorMax',
    ];

    protected function casts(): array
    {
        return [
            'idItems' => 'integer',
            'idEspecies' => 'integer',
            'idSexos' => 'integer',
            'valorMin' => 'decimal:2',
            'valorMax' => 'decimal:2',
        ];
    }

    public function itemsinforme(): BelongsTo
    {
        return $this->belongsTo(Itemsinforme::class, 'idItems', 'idItems');
    }

    public function especie(): BelongsTo
    {
        return $this->belongsTo(Especie::class, 'idEspecies', 'idEspecies');
    }
}
