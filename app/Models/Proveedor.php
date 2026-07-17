<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'idConceptos',
        'cuit',
        'proveedor',
    ];

    protected function casts(): array
    {
        return [
            'idConceptos' => 'integer',
        ];
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(Concepto::class, 'idConceptos', 'id');
    }
}
