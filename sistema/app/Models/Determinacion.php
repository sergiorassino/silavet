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
        'neto',
        'precio',
        'descuento',
        'idDerivaciones',
        'fechaEnvioDeriv',
        'fechaDevolucDeterm',
    ];

    protected function casts(): array
    {
        return [
            'neto' => 'decimal:2',
            'precio' => 'decimal:2',
            'descuento' => 'decimal:2',
            'idDerivaciones' => 'integer',
            'fechaEnvioDeriv' => 'date',
            'fechaDevolucDeterm' => 'date',
        ];
    }

    public function precioFormateado(): string
    {
        return number_format((float) $this->precio, 2, ',', '.');
    }

    public function fechaEnvioDerivFormateada(): string
    {
        return $this->fechaEnvioDeriv?->format('d/m/Y') ?? '—';
    }

    public function fechaDevolucDetermFormateada(): string
    {
        return $this->fechaDevolucDeterm?->format('d/m/Y') ?? '—';
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
