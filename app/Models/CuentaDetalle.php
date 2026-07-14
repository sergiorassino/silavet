<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaDetalle extends Model
{
    protected $table = 'cuentasdetalle';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'idCuentas',
        'nombreCuentasDetalle',
    ];

    protected function casts(): array
    {
        return [
            'idCuentas' => 'integer',
        ];
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'idCuentas', 'id');
    }
}
