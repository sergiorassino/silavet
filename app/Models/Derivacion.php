<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Derivacion extends Model
{
    protected $table = 'derivaciones';

    protected $primaryKey = 'idDerivaciones';

    public $timestamps = false;

    protected $fillable = [
        'derivacion',
    ];
}
