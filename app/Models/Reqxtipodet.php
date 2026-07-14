<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reqxtipodet extends Model
{
    protected $table = 'reqxtipodet';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'idRequerimientos',
        'idTipodeterminaciones',
    ];

    public function requerimiento(): BelongsTo
    {
        return $this->belongsTo(Requerimiento::class, 'idRequerimientos', 'id');
    }

    public function tipodeterminacion(): BelongsTo
    {
        return $this->belongsTo(Tipodeterminacion::class, 'idTipodeterminaciones', 'idTipodeterminaciones');
    }
}
