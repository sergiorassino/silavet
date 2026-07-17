<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    protected $table = 'movimientos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'idCuentas',
        'idTipoMovimiento',
        'idClientes',
        'idPacientes',
        'idConcepto',
        'idProveedores',
        'fechaCheque',
        'numCheque',
        'fechhora',
        'comprobante',
        'monto',
        'obs',
    ];

    protected function casts(): array
    {
        return [
            'fechhora' => 'datetime',
            'fechaCheque' => 'date',
            'monto' => 'decimal:2',
            'idCuentas' => 'integer',
            'idTipoMovimiento' => 'integer',
            'idClientes' => 'integer',
            'idPacientes' => 'integer',
            'idConcepto' => 'integer',
            'idProveedores' => 'integer',
        ];
    }

    public function cuenta(): BelongsTo
    {
        // En labvetciudad idCuentas referencia mediodepago (etiqueta UI: “Cuenta”).
        return $this->belongsTo(MedioDePago::class, 'idCuentas', 'id');
    }

    public function tipoMovimiento(): BelongsTo
    {
        return $this->belongsTo(TipoMovimiento::class, 'idTipoMovimiento', 'id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'idClientes', 'idClientes');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'idPacientes', 'idPacientes');
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(Concepto::class, 'idConcepto', 'id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'idProveedores', 'id');
    }

    public function esEgreso(): bool
    {
        return (int) $this->idTipoMovimiento === TipoMovimiento::EGRESO;
    }

    public function esIngreso(): bool
    {
        return (int) $this->idTipoMovimiento === TipoMovimiento::INGRESO;
    }

    public function montoFormateado(): string
    {
        return number_format((float) $this->monto, 2, ',', '.');
    }

    public function etiquetaPaciente(): string
    {
        if ((int) ($this->idPacientes ?? 0) <= 0) {
            return '';
        }

        $nombre = trim((string) ($this->paciente?->nombre ?? ''));
        $id = (int) $this->idPacientes;

        return $nombre !== '' ? "{$id} - {$nombre}" : (string) $id;
    }
}
