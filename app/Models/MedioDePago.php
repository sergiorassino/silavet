<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedioDePago extends Model
{
    protected $table = 'mediodepago';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'nombreMedioPago',
    ];
}
