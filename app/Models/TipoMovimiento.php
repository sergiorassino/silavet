<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoMovimiento extends Model
{
    public const INGRESO = 1;

    public const EGRESO = 2;

    protected $table = 'tipomovimiento';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'tipoMovimiento',
    ];

    public function conceptos(): HasMany
    {
        return $this->hasMany(Concepto::class, 'tipoConcepto', 'id');
    }
}
