<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Itemsinforme extends Model
{
    protected $table = 'itemsinforme';

    protected $primaryKey = 'idItems';

    public $timestamps = false;

    protected $fillable = [
        'idGrupos',
        'nombreItem',
        'tipoItem',
        'estiloNum',
        'textos',
        'letra',
        'negrita',
        'unidadMedida',
        'unidadMedida2',
        'refCaninos',
        'refFelinos',
        'refEquinos',
        'refBovinos',
        'refPorcinos',
        'refOvinos',
        'refComun',
        'actualiza',
        'idAnalizador',
    ];

    protected function casts(): array
    {
        return [
            'idGrupos' => 'integer',
            'tipoItem' => 'integer',
            'estiloNum' => 'integer',
            'letra' => 'integer',
            'negrita' => 'integer',
            'actualiza' => 'integer',
        ];
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'idGrupos', 'idGrupos');
    }
}
