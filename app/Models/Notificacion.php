<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'fechaCreacion',
        'idClientes',
        'idPacientes',
        'notificacion',
        'leido',
    ];

    protected function casts(): array
    {
        return [
            'fechaCreacion' => 'datetime',
            'idClientes' => 'integer',
            'idPacientes' => 'integer',
            'leido' => 'integer',
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

    public static function leyendaPorDefecto(Paciente $paciente): string
    {
        $nombre = trim((string) ($paciente->nombre ?? ''));
        $protocolo = trim((string) ($paciente->nombreProtocolo ?? ''));

        if ($nombre === '') {
            $nombre = '—';
        }
        if ($protocolo === '') {
            $protocolo = '—';
        }

        // Leyenda legacy (incluye el typo "actualizdo" ya usado en producción).
        return '<p>El informe del paciente '.$nombre.', (protocolo: '.$protocolo.') ha sido actualizdo con nuevos resultados</p>';
    }
}
