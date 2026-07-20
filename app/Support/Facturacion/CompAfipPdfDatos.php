<?php

namespace App\Support\Facturacion;

use App\Models\CompAfip;
use App\Models\Usuario;
use App\Support\Afip\AfipCondicionIvaReceptor;
use App\Support\Afip\AfipComprobanteQrUrl;
use App\Support\Entorno\LabInstitucional;
use Carbon\Carbon;

/**
 * Datos normalizados para PDFs de comprobantes AFIP / comanda.
 */
final class CompAfipPdfDatos
{
    /**
     * @return array<string, mixed>
     */
    public static function desdeCompAfip(CompAfip $comp, ?Usuario $emisor = null): array
    {
        $fecha = $comp->fechaComprobante;
        $fechaYmd = $fecha ? $fecha->format('Ymd') : now()->format('Ymd');
        $fechaIso = $fecha ? $fecha->format('Y-m-d') : now()->format('Y-m-d');
        $caeVto = $comp->CAEFchVto;

        $inicioActiv = null;
        $condIvaEmisor = '';
        $ingresosBrutos = (string) ($comp->cuit ?? '');
        if ($emisor !== null) {
            $inicioActiv = $emisor->inicioActiv
                ? Carbon::parse($emisor->inicioActiv)->format('d/m/Y')
                : null;
            $condIvaEmisor = trim((string) ($emisor->condIva ?? ''));
            $iibb = trim((string) ($emisor->ingresosBrutos ?? ''));
            if ($iibb !== '' && $iibb !== '0') {
                $ingresosBrutos = $iibb;
            }
        }

        $urlQr = '';
        if (! $comp->esComanda() && trim((string) $comp->CAE) !== '' && trim((string) $comp->CAE) !== '0') {
            $urlQr = AfipComprobanteQrUrl::generar([
                'fecha_yyyy_mm_dd' => $fechaIso,
                'cuit' => (string) $comp->cuit,
                'pto_vta' => (int) $comp->PtoVta,
                'tipo_cmp' => (int) $comp->CbteTipo,
                'nro_cmp' => (int) $comp->CbteHasta,
                'importe' => (float) $comp->importe,
                'doc_tipo' => (int) $comp->DocTipo,
                'doc_nro' => (string) $comp->DocNro,
                'cae' => (string) $comp->CAE,
            ]);
        }

        $lab = LabInstitucional::datos();
        $cfg = FacturacionAfipConfig::config();
        $esConsumidorFinal = $comp->esConsumidorFinalSinIdentificar();

        return [
            'CbteTipo' => (int) $comp->CbteTipo,
            'letra' => self::letra((int) $comp->CbteTipo),
            'titulo' => $comp->etiquetaTipo(),
            'es_comanda' => $comp->esComanda(),
            'cuit' => (string) $comp->cuit,
            'PtoVta' => (int) $comp->PtoVta,
            'CbteHasta' => (int) $comp->CbteHasta,
            'numero_formateado' => $comp->numeroFormateado(),
            'razonSocial' => (string) $comp->razonSocial,
            'domicComerc' => (string) $comp->domicComerc,
            'ingresosBrutos' => $ingresosBrutos,
            'inicioActiv' => $inicioActiv,
            'condIvaEmisor' => $condIvaEmisor !== '' ? $condIvaEmisor : 'Responsable Monotributo',
            'fechaComprobante' => $fecha ? $fecha->format('d/m/Y') : '',
            'fecha_ymd' => $fechaYmd,
            'fecha_iso' => $fechaIso,
            'DocTipo' => (int) $comp->DocTipo,
            'DocNro' => (string) $comp->DocNro,
            'es_consumidor_final_sin_identificar' => $esConsumidorFinal,
            'razonSocialCliente' => $esConsumidorFinal ? '' : (string) $comp->razonSocialCliente,
            'condicion_venta' => $esConsumidorFinal
                ? (string) $cfg['condicion_venta_consumidor_final']
                : (string) $cfg['condicion_venta_identificado'],
            'CondicionIVAReceptorId' => (int) $comp->CondicionIVAReceptorId,
            'condicion_iva_etiqueta' => AfipCondicionIvaReceptor::etiquetaDesdeId((int) $comp->CondicionIVAReceptorId),
            'conceptoFacturado' => (string) $comp->conceptoFacturado,
            'importe' => (float) $comp->importe,
            'CAE' => (string) $comp->CAE,
            'CAEFchVto' => $caeVto ? $caeVto->format('d/m/Y') : '',
            'url_qr' => $urlQr,
            'logo_file' => $lab['logo_file'],
        ];
    }

    private static function letra(int $cbteTipo): string
    {
        return match ($cbteTipo) {
            888 => 'X',
            11, 12, 15 => 'C',
            default => 'C',
        };
    }
}
