<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especie extends Model
{
    protected $table = 'especies';

    protected $primaryKey = 'idEspecies';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
    ];

    public function razas(): HasMany
    {
        return $this->hasMany(Raza::class, 'idEspecies', 'idEspecies');
    }
}
