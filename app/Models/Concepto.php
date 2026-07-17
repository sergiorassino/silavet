<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Concepto extends Model
{
    protected $table = 'conceptos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'tipoConcepto',
        'concepto',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'tipoConcepto' => 'integer',
            'orden' => 'integer',
        ];
    }

    public function tipoMovimiento(): BelongsTo
    {
        return $this->belongsTo(TipoMovimiento::class, 'tipoConcepto', 'id');
    }

    public function proveedores(): HasMany
    {
        return $this->hasMany(Proveedor::class, 'idConceptos', 'id');
    }
}
