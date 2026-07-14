<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $primaryKey = 'idUsuarios';

    public $timestamps = false;

    protected $fillable = [
        'idClientes',
        'idRoles',
        'apenom',
        'dni',
        'password',
        'permisoAfip',
        'cuit',
        'razonSocial',
        'domicComerc',
        'condIva',
        'ingresosBrutos',
        'inicioActiv',
        'PtoVta',
        'CbteTipo',
        'NtaCredTipo',
        'Concepto',
        'DocTipo',
        'CondicionIVAReceptorId',
        'key',
        'crt',
        'permisos_ia',
    ];

    protected $hidden = [
        'password',
        'key',
        'crt',
    ];

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'idRoles', 'id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idClientes', 'idClientes');
    }
}
