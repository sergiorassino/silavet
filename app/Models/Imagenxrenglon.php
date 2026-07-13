<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Imagenxrenglon extends Model
{
    protected $table = 'imagenesxrenglon';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'idRenglones',
        'nombreImagen',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'idRenglones' => 'integer',
        ];
    }

    public function renglon(): BelongsTo
    {
        return $this->belongsTo(Renglon::class, 'idRenglones', 'idRenglones');
    }
}
