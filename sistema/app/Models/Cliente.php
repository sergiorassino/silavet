<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $primaryKey = 'idClientes';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono1',
        'telefono2',
        'email',
        'whatsapp',
        'cuit',
    ];
}
