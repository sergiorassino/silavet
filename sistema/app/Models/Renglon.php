<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Renglon extends Model
{
    protected $table = 'renglones';

    protected $primaryKey = 'idRenglones';

    public $timestamps = false;

    protected $fillable = [
        'idClientes',
        'idPacientes',
        'idGrupos',
        'idTipodeterminacion',
        'orden',
        'tipoItem',
        'idItems',
        'valor',
        'valor2',
        'tipoHtml',
        'idAnalizador',
        'mostrar',
    ];

    protected function casts(): array
    {
        return [
            'idClientes' => 'integer',
            'idPacientes' => 'integer',
            'idGrupos' => 'integer',
            'idTipodeterminacion' => 'integer',
            'orden' => 'integer',
            'tipoItem' => 'integer',
            'idItems' => 'integer',
            'tipoHtml' => 'integer',
            'mostrar' => 'integer',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'idPacientes', 'idPacientes');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Itemsinforme::class, 'idItems', 'idItems');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'idGrupos', 'idGrupos');
    }

    public function tipodeterminacion(): BelongsTo
    {
        return $this->belongsTo(Tipodeterminacion::class, 'idTipodeterminacion', 'idTipodeterminaciones');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(Imagenxrenglon::class, 'idRenglones', 'idRenglones');
    }
}
