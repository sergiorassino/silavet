<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paciente extends Model
{
    /** Protocolo / caso analítico habitual. */
    public const TIPO_PROTOCOLO = 1;

    /**
     * Ingreso de tesorería (pago global: cliente + importe + medio).
     * Alias histórico: TIPO_PAGO_GLOBAL.
     */
    public const TIPO_INGRESO = 2;

    /** @deprecated Usar TIPO_INGRESO */
    public const TIPO_PAGO_GLOBAL = self::TIPO_INGRESO;

    /** Egreso de tesorería (cuenta / proveedor + importe + medio). */
    public const TIPO_EGRESO = 3;

    /**
     * Cliente interno usado por NeoLab al registrar egresos (`idClientes = 1`).
     */
    public const ID_CLIENTES_EGRESO = 1;

    protected $table = 'pacientes';

    protected $primaryKey = 'idPacientes';

    public $timestamps = false;

    protected $fillable = [
        'idClientes',
        'idUsuarios',
        'idEspecies',
        'idRazas',
        'idCuentasdetalle',
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
            'fechhoy' => 'datetime',
            'fechaEnvioDeriv' => 'date',
            'tipoRegistro' => 'integer',
            'neto' => 'decimal:2',
            'precio' => 'decimal:2',
            'cadete' => 'decimal:2',
            'pagado' => 'decimal:2',
            'descuento' => 'decimal:2',
            'saldo' => 'decimal:2',
        ];
    }

    /**
     * Orden de listado (lab, autogestión, cuenta corriente): días más recientes
     * primero; dentro de cada día, protocolos/pacientes y luego pagos.
     *
     * El saldo de cada fila se acumula en el orden inverso
     * ({@see scopeOrdenAcumulacionSaldo}), de modo que la primera fila del
     * listado muestra el saldo actual de la cuenta.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOrdenListado($query)
    {
        $tabla = $query->getModel()->getTable();

        return $query
            ->orderByRaw("DATE({$tabla}.fechhoy) DESC")
            ->orderBy("{$tabla}.tipoRegistro")
            ->orderBy("{$tabla}.fechhoy")
            ->orderBy("{$tabla}.nombreProtocolo")
            ->orderBy("{$tabla}.idPacientes");
    }

    /**
     * Orden de acumulación del saldo corrido: inverso exacto de
     * {@see scopeOrdenListado}. Así el último movimiento acumulado es el
     * primero del listado y su saldo coincide con el saldo actual.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOrdenAcumulacionSaldo($query)
    {
        $tabla = $query->getModel()->getTable();

        return $query
            ->orderByRaw("DATE({$tabla}.fechhoy) ASC")
            ->orderByDesc("{$tabla}.tipoRegistro")
            ->orderByDesc("{$tabla}.fechhoy")
            ->orderByDesc("{$tabla}.nombreProtocolo")
            ->orderByDesc("{$tabla}.idPacientes");
    }

    /**
     * @deprecated Usar {@see scopeOrdenAcumulacionSaldo}
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOrdenCronologico($query)
    {
        return $query->ordenAcumulacionSaldo();
    }

    public function esPagoGlobal(): bool
    {
        return $this->esIngreso();
    }

    public function esIngreso(): bool
    {
        return (int) ($this->tipoRegistro ?? 0) === self::TIPO_INGRESO;
    }

    public function esEgreso(): bool
    {
        return (int) ($this->tipoRegistro ?? 0) === self::TIPO_EGRESO;
    }

    public function esMovimientoTesoreria(): bool
    {
        return $this->esIngreso() || $this->esEgreso();
    }

    public function etiquetaMovimiento(): string
    {
        return match ((int) ($this->tipoRegistro ?? 0)) {
            self::TIPO_INGRESO => 'Ingreso',
            self::TIPO_EGRESO => 'Egreso',
            default => '—',
        };
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'idClientes', 'idClientes');
    }

    public function cuentaDetalle(): BelongsTo
    {
        return $this->belongsTo(CuentaDetalle::class, 'idCuentasdetalle', 'id');
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

    public function medioDePago(): BelongsTo
    {
        return $this->belongsTo(MedioDePago::class, 'idMediodepago', 'id');
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
        if ($this->esPagoGlobal()) {
            return 'vl-pacientes-row--pago-global';
        }

        return \App\Support\Resultados\ResultadosEstadosCatalog::claseCssFila($this->estado);
    }

    public function precioFormateado(): string
    {
        return number_format((float) $this->precio, 2, ',', '.');
    }

    public function pagadoFormateado(): string
    {
        return number_format((float) $this->pagado, 2, ',', '.');
    }

    /**
     * Precio de lista del protocolo.
     * Con columna neto (modelo nuevo): suma de netos de determinaciones.
     * Legacy: el campo precio era el importe de lista.
     * Los pagos (tipoRegistro = 2) no tienen cargo: siempre 0.
     */
    public function precioLista(): float
    {
        if ($this->esIngreso()) {
            return 0.0;
        }

        $neto = round((float) ($this->neto ?? 0), 2);
        if ($neto > 0) {
            return $neto;
        }

        return round((float) ($this->precio ?? 0), 2);
    }

    /**
     * Precio con descuento (cargo neto del movimiento).
     * Con neto: precio ya viene consolidado con descuento.
     * Legacy: precio − descuento.
     * Los pagos (tipoRegistro = 2) no tienen cargo: siempre 0.
     */
    public function precioConDescuentoImporte(): float
    {
        if ($this->esIngreso()) {
            return 0.0;
        }

        $neto = round((float) ($this->neto ?? 0), 2);
        $precio = round((float) ($this->precio ?? 0), 2);
        $descuento = round((float) ($this->descuento ?? 0), 2);

        if ($neto > 0) {
            return $precio;
        }

        return \App\Support\Precios\PrecioDeterminacionResolver::precioConDescuento($precio, $descuento);
    }

    public function descuentoImporte(): float
    {
        if ($this->esIngreso()) {
            return 0.0;
        }

        $neto = round((float) ($this->neto ?? 0), 2);
        $precio = round((float) ($this->precio ?? 0), 2);
        $descuento = round((float) ($this->descuento ?? 0), 2);

        if ($neto > 0) {
            return round(max(0, $neto - $precio), 2);
        }

        return $descuento;
    }

    /**
     * Importe cobrado en este movimiento.
     * En pagos globales (tipoRegistro = 2) el importe suele estar en pagado;
     * algunos registros legacy lo guardaron en precio.
     */
    public function importePagadoMovimiento(): float
    {
        $pagado = round((float) ($this->pagado ?? 0), 2);
        if ($pagado > 0) {
            return $pagado;
        }

        if ($this->esIngreso()) {
            return round(abs((float) ($this->precio ?? 0)), 2);
        }

        return 0.0;
    }

    public function importePagadoMovimientoFormateado(): string
    {
        return number_format($this->importePagadoMovimiento(), 2, ',', '.');
    }

    public function precioListaFormateado(): string
    {
        return number_format($this->precioLista(), 2, ',', '.');
    }

    public function precioConDescuentoFormateado(): string
    {
        return number_format($this->precioConDescuentoImporte(), 2, ',', '.');
    }

    public function descuentoFormateado(): string
    {
        return number_format($this->descuentoImporte(), 2, ',', '.');
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
