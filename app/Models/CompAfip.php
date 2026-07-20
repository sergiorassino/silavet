<?php

namespace App\Models;

use App\Support\Facturacion\FacturacionAfipConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class CompAfip extends Model
{
    protected $table = 'compafip';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'idPacientes',
        'cuit',
        'PtoVta',
        'CbteTipo',
        'Concepto',
        'DocTipo',
        'DocNro',
        'razonSocial',
        'domicComerc',
        'razonSocialCliente',
        'importe',
        'FechServDesde',
        'FechServHasta',
        'fechaComprobante',
        'CbteHasta',
        'CondicionIVAReceptorId',
        'conceptoFacturado',
        'CAE',
        'CAEFchVto',
        'idCompAfipAsoc',
    ];

    protected function casts(): array
    {
        return [
            'PtoVta' => 'integer',
            'CbteTipo' => 'integer',
            'Concepto' => 'integer',
            'DocTipo' => 'integer',
            'importe' => 'float',
            'FechServDesde' => 'date',
            'FechServHasta' => 'date',
            'fechaComprobante' => 'date',
            'CbteHasta' => 'integer',
            'CondicionIVAReceptorId' => 'integer',
            'CAEFchVto' => 'date',
            'idCompAfipAsoc' => 'integer',
        ];
    }

    public static function tieneColumnaAsoc(): bool
    {
        return Schema::hasColumn('compafip', 'idCompAfipAsoc');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'idPacientes', 'idPacientes');
    }

    public function comprobanteAsociado(): BelongsTo
    {
        return $this->belongsTo(self::class, 'idCompAfipAsoc', 'id');
    }

    public function esComanda(): bool
    {
        return (int) $this->CbteTipo === FacturacionAfipConfig::CBTE_COMANDA
            || (int) $this->CbteTipo === (int) FacturacionAfipConfig::config()['comanda_tipo'];
    }

    public function esNotaCredito(): bool
    {
        $nc = (int) FacturacionAfipConfig::config()['nota_credito_tipo'];

        return $nc > 0 && (int) $this->CbteTipo === $nc;
    }

    public function esFactura(): bool
    {
        return ! $this->esComanda() && ! $this->esNotaCredito() && (int) $this->CbteTipo > 0;
    }

    public function etiquetaTipo(): string
    {
        return match ((int) $this->CbteTipo) {
            11 => 'Factura C',
            12 => 'Nota de crédito C',
            15 => 'Recibo C',
            888 => 'Comanda',
            default => $this->esComanda()
                ? 'Comanda'
                : ($this->esNotaCredito() ? 'Nota de crédito' : 'Comprobante'),
        };
    }

    public function numeroFormateado(): string
    {
        if ($this->esComanda()) {
            return (string) ((int) $this->CbteHasta);
        }

        return str_pad((string) ((int) $this->PtoVta), 4, '0', STR_PAD_LEFT)
            .'-'
            .str_pad((string) ((int) $this->CbteHasta), 8, '0', STR_PAD_LEFT);
    }

    public function esConsumidorFinalSinIdentificar(): bool
    {
        $cfg = FacturacionAfipConfig::config();
        if ((int) $this->DocTipo !== (int) $cfg['doc_tipo_consumidor_final']) {
            return false;
        }

        $docNro = preg_replace('/\D/', '', (string) ($this->DocNro ?? '')) ?? '';

        return $docNro === '' || $docNro === '0';
    }
}
