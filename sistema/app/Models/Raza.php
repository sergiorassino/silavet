<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Raza extends Model
{
    protected $table = 'razas';

    protected $primaryKey = 'idRazas';

    public $timestamps = false;

    protected $fillable = [
        'idEspecies',
        'nombre',
    ];

    public function especie(): BelongsTo
    {
        return $this->belongsTo(Especie::class, 'idEspecies', 'idEspecies');
    }
}
