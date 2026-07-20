<?php

namespace App\Support\Facturacion;

use App\Models\Usuario;
use Illuminate\Support\Facades\Schema;

/**
 * Configuración de facturación AFIP por tenant + emisor (usuario).
 */
final class FacturacionAfipConfig
{
    public const MODO_PACIENTE = 'paciente';

    public const MODO_MOVIMIENTO = 'movimiento';

    public const FORMATO_A4 = 'A4';

    public const FORMATO_TERMICA80 = 'termica80';

    public const CBTE_COMANDA = 888;

    /**
     * @return array<string, mixed>
     */
    public static function config(): array
    {
        /** @var array<string, mixed> $cfg */
        $cfg = (array) config('tenant.facturacion_afip', []);

        $simular = ! empty($cfg['simular']);
        if (! $simular && ! empty($cfg['simular_local']) && app()->environment('local')) {
            $simular = true;
        }

        $modo = (string) ($cfg['modo'] ?? self::MODO_PACIENTE);
        if (! in_array($modo, [self::MODO_PACIENTE, self::MODO_MOVIMIENTO], true)) {
            $modo = self::MODO_PACIENTE;
        }

        return array_merge($cfg, [
            'habilitado' => ! empty($cfg['habilitado']),
            'modo' => $modo,
            'simular' => $simular,
            'produccion' => ! empty($cfg['produccion']),
            'cbte_tipo' => (int) ($cfg['cbte_tipo'] ?? 11),
            'nota_credito_tipo' => (int) ($cfg['nota_credito_tipo'] ?? 12),
            'comanda_tipo' => (int) ($cfg['comanda_tipo'] ?? self::CBTE_COMANDA),
            'concepto' => (int) ($cfg['concepto'] ?? 2),
            'doc_tipo_dni' => (int) ($cfg['doc_tipo_dni'] ?? 96),
            'doc_tipo_cuit' => (int) ($cfg['doc_tipo_cuit'] ?? 80),
            'doc_tipo_consumidor_final' => (int) ($cfg['doc_tipo_consumidor_final'] ?? 99),
            'importe_minimo_identificacion_cf' => (float) ($cfg['importe_minimo_identificacion_cf'] ?? 10_000_000),
            'condicion_iva_receptor_id' => (int) ($cfg['condicion_iva_receptor_id'] ?? 5),
        ]);
    }

    public static function habilitada(): bool
    {
        return (bool) self::config()['habilitado'];
    }

    public static function modo(): string
    {
        return (string) self::config()['modo'];
    }

    public static function esModoPaciente(): bool
    {
        return self::modo() === self::MODO_PACIENTE;
    }

    public static function esModoMovimiento(): bool
    {
        return self::modo() === self::MODO_MOVIMIENTO;
    }

    /**
     * Config lista para WSAA/WSFE a partir del usuario emisor.
     *
     * @return array<string, mixed>
     */
    public static function paraEmision(Usuario $emisor): array
    {
        $cfg = self::config();
        $id = (int) $emisor->idUsuarios;
        $key = trim((string) ($emisor->key ?? ''));
        $crt = trim((string) ($emisor->crt ?? ''));

        $cbteTipo = (int) ($emisor->CbteTipo ?: $cfg['cbte_tipo']);
        $ncTipo = (int) ($emisor->NtaCredTipo ?: $cfg['nota_credito_tipo']);
        $concepto = (int) ($emisor->Concepto ?: $cfg['concepto']);

        return array_merge($cfg, [
            'cert_usuario_id' => (string) $id,
            'cert_key' => $key,
            'cert_crt' => $crt,
            'cbte_tipo' => $cbteTipo > 0 ? $cbteTipo : (int) $cfg['cbte_tipo'],
            'nota_credito_tipo' => $ncTipo > 0 ? $ncTipo : (int) $cfg['nota_credito_tipo'],
            'cbte_tipo_asociado' => $cbteTipo > 0 ? $cbteTipo : (int) $cfg['cbte_tipo'],
            'concepto' => $concepto > 0 ? $concepto : (int) $cfg['concepto'],
            'doc_tipo' => (int) ($emisor->DocTipo ?: $cfg['doc_tipo_dni']),
        ]);
    }

    public static function emisorPuedeFacturar(?Usuario $emisor): bool
    {
        if ($emisor === null || (int) $emisor->permisoAfip !== 1) {
            return false;
        }

        $cuit = preg_replace('/\D/', '', (string) ($emisor->cuit ?? '')) ?? '';
        if (strlen($cuit) !== 11) {
            return false;
        }

        if ((int) ($emisor->PtoVta ?? 0) <= 0) {
            return false;
        }

        $cfg = self::paraEmision($emisor);
        if (! empty($cfg['simular'])) {
            return true;
        }

        $base = base_path('afipSE/cert/'.(int) $emisor->idUsuarios);
        $key = $base.'/'.trim((string) ($emisor->key ?? ''));
        $crt = $base.'/'.trim((string) ($emisor->crt ?? ''));

        return is_file($key) && is_file($crt);
    }

    public static function formatoImpresion(): string
    {
        $default = self::FORMATO_A4;
        if (! Schema::hasTable('entorno') || ! Schema::hasColumn('entorno', 'afipFormatoImpresion')) {
            return $default;
        }

        $valor = trim((string) (\App\Models\Entorno::query()->orderBy('id')->value('afipFormatoImpresion') ?? ''));

        return $valor === self::FORMATO_TERMICA80 ? self::FORMATO_TERMICA80 : self::FORMATO_A4;
    }
}
