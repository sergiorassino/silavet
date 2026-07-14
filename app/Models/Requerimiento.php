<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requerimiento extends Model
{
    protected $table = 'requerimientos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'titulo',
        'requerimiento',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('ordenAlfabetico', function (Builder $query) {
            $query->orderByRaw('LOWER(titulo) ASC')->orderBy('id');
        });
    }

    public function vinculosTipodeterminacion(): HasMany
    {
        return $this->hasMany(Reqxtipodet::class, 'idRequerimientos', 'id');
    }

    public function tipodeterminaciones(): BelongsToMany
    {
        return $this->belongsToMany(
            Tipodeterminacion::class,
            'reqxtipodet',
            'idRequerimientos',
            'idTipodeterminaciones',
            'id',
            'idTipodeterminaciones'
        );
    }
}
