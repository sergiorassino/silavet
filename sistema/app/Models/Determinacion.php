<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Determinacion extends Model
{
    protected $table = 'determinaciones';

    protected $primaryKey = 'idDeterminaciones';

    public $timestamps = false;

    protected $fillable = [
        'idClientes',
        'idPacientes',
        'idTipodeterminaciones',
        'precio',
        'descuento',
        'idDerivaciones',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'descuento' => 'decimal:2',
            'idDerivaciones' => 'integer',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'idPacientes', 'idPacientes');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'idClientes', 'idClientes');
    }

    public function tipodeterminacion(): BelongsTo
    {
        return $this->belongsTo(Tipodeterminacion::class, 'idTipodeterminaciones', 'idTipodeterminaciones');
    }

    public function derivacion(): BelongsTo
    {
        return $this->belongsTo(Derivacion::class, 'idDerivaciones', 'idDerivaciones');
    }
}
