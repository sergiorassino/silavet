<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $primaryKey = 'idPacientes';

    public $timestamps = false;

    protected $fillable = [
        'idClientes',
        'idUsuarios',
        'idEspecies',
        'idRazas',
        'idCuentasDetalle',
        'tipoRegistro',
        'fechhoy',
        'nombreProtocolo',
        'nombre',
        'propietario',
        'email',
        'whatsapp',
        'sexo',
        'fechnaci',
        'edad',
        'estado',
        'neto',
        'precio',
        'fechaEnvioDeriv',
        'cadete',
        'pagado',
        'descuento',
        'saldo',
        'idMediodepago',
        'urlExcel',
        'urlPdf',
        'adjunto',
        'observaciones',
        'clinica',
        'obsPriv',
    ];

    protected function casts(): array
    {
        return [
            'fechhoy' => 'date',
            'fechaEnvioDeriv' => 'date',
            'neto' => 'decimal:2',
            'precio' => 'decimal:2',
            'cadete' => 'decimal:2',
            'pagado' => 'decimal:2',
            'descuento' => 'decimal:2',
            'saldo' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'idClientes', 'idClientes');
    }

    public function especie(): BelongsTo
    {
        return $this->belongsTo(Especie::class, 'idEspecies', 'idEspecies');
    }

    public function raza(): BelongsTo
    {
        return $this->belongsTo(Raza::class, 'idRazas', 'idRazas');
    }

    public function medicoSolicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'idUsuarios', 'idUsuarios');
    }

    public function determinaciones(): HasMany
    {
        return $this->hasMany(Determinacion::class, 'idPacientes', 'idPacientes');
    }

    public function renglones(): HasMany
    {
        return $this->hasMany(Renglon::class, 'idPacientes', 'idPacientes');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'idPacientes', 'idPacientes');
    }

    public function notificacion(): HasOne
    {
        return $this->hasOne(Notificacion::class, 'idPacientes', 'idPacientes')->latestOfMany('id');
    }

    public function filaClaseCss(): string
    {
        return match (\App\Support\Resultados\ResultadosEstadosCatalog::normalizar($this->estado)) {
            'Parcial' => 'vl-pacientes-row--parcial',
            'Final' => 'vl-pacientes-row--final',
            'Final/Env' => 'vl-pacientes-row--final-env',
            default => 'vl-pacientes-row--en-proc',
        };
    }

    public function precioFormateado(): string
    {
        return number_format((float) $this->precio, 2, ',', '.');
    }

    public function fechhoyFormateada(): string
    {
        return $this->fechhoy?->format('d/m/Y') ?? '—';
    }

    public function tieneAdjunto(): bool
    {
        return trim((string) ($this->adjunto ?? '')) !== '';
    }

    public function tieneNotificacion(): bool
    {
        if ($this->relationLoaded('notificacion')) {
            return $this->getRelation('notificacion') !== null
                && trim((string) ($this->getRelation('notificacion')->notificacion ?? '')) !== '';
        }

        if ($this->relationLoaded('notificaciones')) {
            return $this->notificaciones->contains(
                fn (Notificacion $n) => trim((string) ($n->notificacion ?? '')) !== ''
            );
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('notificaciones')) {
            return false;
        }

        return $this->notificaciones()
            ->whereNotNull('notificacion')
            ->where('notificacion', '!=', '')
            ->exists();
    }
}
