<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuenta extends Model
{
    protected $table = 'cuentas';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'nombreCuenta',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(CuentaDetalle::class, 'idCuentas', 'id');
    }
}
