<?php

namespace App\Support\Facturacion;

use App\Models\Entorno;
use App\Models\Usuario;
use App\Support\Afip\AfipCertificadosStorage;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Configuración de facturación AFIP por tenant + emisor (usuario).
 */
final class FacturacionAfipConfig
{
    public const MODO_PACIENTE = 'paciente';

    public const MODO_MOVIMIENTO = 'movimiento';

    /** Tesorería tabla `movimientos` (labvetciudad). */
    public const MODO_MOVIMIENTO_CAJA = 'movimiento_caja';

    public const RECEPTOR_CLIENTE = 'cliente';

    public const RECEPTOR_PACIENTE = 'paciente';

    public const RECEPTOR_CONSUMIDOR_FINAL = 'consumidor_final';

    public const FORMATO_A4 = 'A4';

    public const FORMATO_TERMICA80 = 'termica80';

    public const CBTE_COMANDA = 888;

    /** Factura C, sin IVA discriminado. Es el régimen de los laboratorios que ya facturan. */
    public const REGIMEN_MONOTRIBUTO = 'monotributo';

    /** Factura A o B según el receptor, con IVA discriminado. */
    public const REGIMEN_RESPONSABLE_INSCRIPTO = 'responsable_inscripto';

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
        if (! in_array($modo, [self::MODO_PACIENTE, self::MODO_MOVIMIENTO, self::MODO_MOVIMIENTO_CAJA], true)) {
            $modo = self::MODO_PACIENTE;
        }

        $regimen = (string) ($cfg['regimen'] ?? self::REGIMEN_MONOTRIBUTO);
        if (! in_array($regimen, [self::REGIMEN_MONOTRIBUTO, self::REGIMEN_RESPONSABLE_INSCRIPTO], true)) {
            $regimen = self::REGIMEN_MONOTRIBUTO;
        }

        $precioIncluyeIva = true;
        if (array_key_exists('precio_incluye_iva', $cfg)) {
            $precioIncluyeIva = (bool) $cfg['precio_incluye_iva'];
        }

        return array_merge($cfg, [
            'habilitado' => ! empty($cfg['habilitado']),
            'modo' => $modo,
            'regimen' => $regimen,
            'alicuota_iva' => (float) ($cfg['alicuota_iva'] ?? 21),
            'precio_incluye_iva' => $precioIncluyeIva,
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
            'condicion_venta_consumidor_final' => (string) ($cfg['condicion_venta_consumidor_final'] ?? 'Contado / Transferencia Bancaria'),
            'condicion_venta_identificado' => (string) ($cfg['condicion_venta_identificado'] ?? 'Cuenta Corriente'),
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

    /**
     * Icono AFIP en Gestión de pacientes (lvm, neolab, civetfranca).
     * La columna Pagado no usa este método: ver TesoreriaConfig::columnaPagadoHabilitada().
     */
    public static function enListadoPacientes(): bool
    {
        return self::habilitada() && self::esModoPaciente();
    }

    public static function esModoMovimiento(): bool
    {
        return self::modo() === self::MODO_MOVIMIENTO;
    }

    public static function esModoMovimientoCaja(): bool
    {
        return self::modo() === self::MODO_MOVIMIENTO_CAJA;
    }

    public static function regimen(): string
    {
        return (string) self::config()['regimen'];
    }

    public static function esResponsableInscripto(): bool
    {
        return self::regimen() === self::REGIMEN_RESPONSABLE_INSCRIPTO;
    }

    public static function esMonotributo(): bool
    {
        return ! self::esResponsableInscripto();
    }

    public static function alicuotaIva(): float
    {
        return (float) self::config()['alicuota_iva'];
    }

    public static function precioIncluyeIva(): bool
    {
        return (bool) self::config()['precio_incluye_iva'];
    }

    /**
     * @return list<string>
     */
    public static function tiposReceptorCaja(): array
    {
        return [
            self::RECEPTOR_CLIENTE,
            self::RECEPTOR_PACIENTE,
            self::RECEPTOR_CONSUMIDOR_FINAL,
        ];
    }

    public static function esTipoReceptorCajaValido(string $tipo): bool
    {
        return in_array($tipo, self::tiposReceptorCaja(), true);
    }

    /**
     * Config lista para WSAA/WSFE a partir del usuario emisor.
     *
     * @return array<string, mixed>
     */
    public static function paraEmision(Usuario $emisor): array
    {
        $cfg = self::config();

        if (self::esResponsableInscripto()) {
            $ficha = self::exigirFichaEmisor();

            return array_merge($cfg, [
                'cert_laboratorio' => true,
                'cert_usuario_id' => '0',
                'cert_key' => $ficha['key'],
                'cert_crt' => $ficha['crt'],
                'cbte_tipo' => (int) $cfg['cbte_tipo'],
                'nota_credito_tipo' => (int) $cfg['nota_credito_tipo'],
                'cbte_tipo_asociado' => (int) $cfg['cbte_tipo'],
                'concepto' => $ficha['concepto'],
                'doc_tipo' => (int) $cfg['doc_tipo_dni'],
            ]);
        }

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

    /**
     * Ficha fiscal del laboratorio. No lee usuarios.
     *
     * @return array{
     *     cuit: string,
     *     pto_vta: int,
     *     razon_social: string,
     *     domicilio: string,
     *     cond_iva: string,
     *     ingresos_brutos: string,
     *     inicio_activ: string,
     *     concepto: int,
     *     key: string,
     *     crt: string
     * }
     */
    public static function exigirFichaEmisor(): array
    {
        $faltan = self::faltantesFichaEmisor();
        if ($faltan !== []) {
            throw new RuntimeException(
                'No se puede facturar como responsable inscripto. Faltan en Parámetros del Sistema, solapa Configuración Arca: '
                .implode(', ', $faltan).'.'
            );
        }

        $entorno = Entorno::query()->orderBy('id')->first();
        if ($entorno === null) {
            throw new RuntimeException(
                'No se puede facturar como responsable inscripto. Falta el registro de parámetros del laboratorio.'
            );
        }

        $cuit = preg_replace('/\D/', '', (string) ($entorno->afipCuit ?? '')) ?? '';

        return [
            'cuit' => $cuit,
            'pto_vta' => (int) $entorno->afipPtoVta,
            'razon_social' => trim((string) $entorno->afipRazonSocial),
            'domicilio' => trim((string) $entorno->afipDomicComerc),
            'cond_iva' => self::textoEntorno((string) ($entorno->afipCondIva ?? '')),
            'ingresos_brutos' => self::textoEntorno((string) ($entorno->afipIngresosBrutos ?? '')),
            'inicio_activ' => self::textoEntorno((string) ($entorno->afipInicioActiv ?? '')),
            'concepto' => (int) $entorno->afipConcepto,
            'key' => self::textoEntorno((string) ($entorno->afipKey ?? '')),
            'crt' => self::textoEntorno((string) ($entorno->afipCrt ?? '')),
        ];
    }

    /**
     * @return list<string>
     */
    public static function faltantesFichaEmisor(): array
    {
        if (! Schema::hasTable('entorno')) {
            return ['la tabla entorno'];
        }

        foreach ([
            'afipCuit',
            'afipRazonSocial',
            'afipDomicComerc',
            'afipPtoVta',
            'afipConcepto',
            'afipKey',
            'afipCrt',
        ] as $columna) {
            if (! Schema::hasColumn('entorno', $columna)) {
                return ['las columnas de ARCA en entorno (php artisan migrate)'];
            }
        }

        $entorno = Entorno::query()->orderBy('id')->first();
        if ($entorno === null) {
            return ['el registro de parámetros del laboratorio'];
        }

        $faltan = [];
        $cuit = preg_replace('/\D/', '', (string) ($entorno->afipCuit ?? '')) ?? '';
        if (strlen($cuit) !== 11) {
            $faltan[] = 'CUIT';
        }
        if (self::textoEntorno((string) ($entorno->afipRazonSocial ?? '')) === '') {
            $faltan[] = 'razón social';
        }
        if (self::textoEntorno((string) ($entorno->afipDomicComerc ?? '')) === '') {
            $faltan[] = 'domicilio comercial';
        }
        if ((int) ($entorno->afipPtoVta ?? 0) <= 0) {
            $faltan[] = 'punto de venta';
        }
        if (! in_array((int) ($entorno->afipConcepto ?? 0), [1, 2, 3], true)) {
            $faltan[] = 'concepto';
        }

        if (empty(self::config()['simular'])) {
            $key = self::textoEntorno((string) ($entorno->afipKey ?? ''));
            $crt = self::textoEntorno((string) ($entorno->afipCrt ?? ''));
            if ($key === '' || ! AfipCertificadosStorage::existeLaboratorio($key)) {
                $faltan[] = 'clave privada';
            }
            if ($crt === '' || ! AfipCertificadosStorage::existeLaboratorio($crt)) {
                $faltan[] = 'certificado';
            }
        }

        return $faltan;
    }

    /**
     * Textos del emisor para el PDF. Vacío si la ficha no está cargada: no usa el usuario.
     *
     * @return array{cond_iva: string, ingresos_brutos: string, inicio_activ: string}
     */
    public static function leyendaEmisor(): array
    {
        $vacio = ['cond_iva' => '', 'ingresos_brutos' => '', 'inicio_activ' => ''];
        if (! Schema::hasTable('entorno')) {
            return $vacio;
        }

        foreach (['afipCondIva', 'afipIngresosBrutos', 'afipInicioActiv'] as $columna) {
            if (! Schema::hasColumn('entorno', $columna)) {
                return $vacio;
            }
        }

        $entorno = Entorno::query()->orderBy('id')->first();
        if ($entorno === null) {
            return $vacio;
        }

        return [
            'cond_iva' => self::textoEntorno((string) ($entorno->afipCondIva ?? '')),
            'ingresos_brutos' => self::textoEntorno((string) ($entorno->afipIngresosBrutos ?? '')),
            'inicio_activ' => self::textoEntorno((string) ($entorno->afipInicioActiv ?? '')),
        ];
    }

    public static function mensajeEmisorNoDisponible(?Usuario $emisor): string
    {
        if ($emisor === null || (int) $emisor->permisoAfip !== 1) {
            return 'El usuario actual no tiene permiso AFIP.';
        }

        if (! self::esResponsableInscripto()) {
            $id = (int) ($emisor->idUsuarios ?? 0);

            return 'El usuario actual no puede emitir: falta CUIT, punto de venta o certificados en afipSE/cert/'.$id.'/.';
        }

        $faltan = self::faltantesFichaEmisor();
        if ($faltan === []) {
            return '';
        }

        return 'No se puede facturar como responsable inscripto. Faltan en Parámetros del Sistema, solapa Configuración Arca: '
            .implode(', ', $faltan).'.';
    }

    public static function emisorPuedeFacturar(?Usuario $emisor): bool
    {
        if ($emisor === null || (int) $emisor->permisoAfip !== 1) {
            return false;
        }

        if (self::esResponsableInscripto()) {
            return self::faltantesFichaEmisor() === [];
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

        $id = (int) $emisor->idUsuarios;

        return AfipCertificadosStorage::existe($id, (string) ($emisor->key ?? ''))
            && AfipCertificadosStorage::existe($id, (string) ($emisor->crt ?? ''));
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

    private static function textoEntorno(string $valor): string
    {
        $valor = trim($valor);

        return ($valor === '' || $valor === '0') ? '' : $valor;
    }
}
