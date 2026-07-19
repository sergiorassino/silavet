<?php

namespace App\Http\Controllers\Facturacion;

use App\Models\CompAfip;
use App\Models\Usuario;
use App\Support\Facturacion\CompAfipPdfDatos;
use App\Support\Facturacion\FacturacionAfipConfig;
use App\Support\Facturacion\Pdf\CompAfipA4Tcpdf;
use App\Support\Facturacion\Pdf\CompAfipComandaA4Tcpdf;
use App\Support\Facturacion\Pdf\CompAfipComandaTcpdf;
use App\Support\Facturacion\Pdf\CompAfipTermica80Tcpdf;
use App\Support\PermisosIaCatalog;
use App\Support\Security\OpaqueRouteToken;
use Illuminate\Http\Response;

final class CompAfipPdfController
{
    public function __invoke(string $ref): Response
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);
        abort_unless(FacturacionAfipConfig::habilitada(), 403);

        $decoded = OpaqueRouteToken::decodeCompAfip($ref);
        abort_if($decoded === null, 404);

        $comp = CompAfip::query()->find($decoded['id']);
        abort_if($comp === null, 404);

        $emisor = null;
        $cuit = preg_replace('/\D/', '', (string) $comp->cuit) ?? '';
        if ($cuit !== '') {
            $emisor = Usuario::query()
                ->where('cuit', $cuit)
                ->orWhere('cuit', $comp->cuit)
                ->orderBy('idUsuarios')
                ->first();
        }

        $datos = CompAfipPdfDatos::desdeCompAfip($comp, $emisor);
        $nombre = 'comp-afip-'.$comp->id.'.pdf';
        $termica = FacturacionAfipConfig::formatoImpresion() === FacturacionAfipConfig::FORMATO_TERMICA80;

        if ($comp->esComanda()) {
            if ($termica) {
                return CompAfipComandaTcpdf::respuestaHttp(
                    CompAfipComandaTcpdf::generar($datos),
                    $nombre
                );
            }

            return CompAfipComandaA4Tcpdf::respuestaHttp(
                CompAfipComandaA4Tcpdf::generar($datos),
                $nombre
            );
        }

        if ($termica) {
            return CompAfipTermica80Tcpdf::respuestaHttp(
                CompAfipTermica80Tcpdf::generar($datos),
                $nombre
            );
        }

        return CompAfipA4Tcpdf::respuestaHttp(
            CompAfipA4Tcpdf::generar($datos),
            $nombre
        );
    }
}
