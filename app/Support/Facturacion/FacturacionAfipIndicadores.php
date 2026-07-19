<?php

namespace App\Support\Facturacion;

use App\Models\CompAfip;
use Illuminate\Support\Facades\Schema;

/**
 * Indicadores de UI para listados (icono AFIP naranja = factura vigente o comanda).
 */
final class FacturacionAfipIndicadores
{
    /**
     * Ids de pacientes con factura vigente (sin NC que la anule) o con comanda.
     *
     * @param  list<int|string>  $idsPacientes
     * @return array<int, true>  mapa idPacientes => true
     */
    public static function mapaConEmitido(array $idsPacientes): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($id): int => (int) $id,
            $idsPacientes
        ), static fn (int $id): bool => $id > 0)));

        if ($ids === [] || ! Schema::hasTable('compafip')) {
            return [];
        }

        $cfg = FacturacionAfipConfig::config();
        $comandaTipo = (int) $cfg['comanda_tipo'];
        $ncTipo = (int) $cfg['nota_credito_tipo'];

        $idsStr = array_map(static fn (int $id): string => (string) $id, $ids);

        $columnas = ['id', 'idPacientes', 'CbteTipo'];
        if (CompAfip::tieneColumnaAsoc()) {
            $columnas[] = 'idCompAfipAsoc';
        }

        $rows = CompAfip::query()
            ->whereIn('idPacientes', $idsStr)
            ->get($columnas);

        if ($rows->isEmpty()) {
            return [];
        }

        $anuladas = [];
        if (CompAfip::tieneColumnaAsoc()) {
            foreach ($rows as $row) {
                if ($ncTipo > 0 && (int) $row->CbteTipo === $ncTipo) {
                    $asoc = (int) ($row->idCompAfipAsoc ?? 0);
                    if ($asoc > 0) {
                        $anuladas[$asoc] = true;
                    }
                }
            }
        }

        $out = [];
        foreach ($rows as $row) {
            $idPac = (int) $row->idPacientes;
            $tipo = (int) $row->CbteTipo;

            if ($tipo === $comandaTipo || $tipo === FacturacionAfipConfig::CBTE_COMANDA) {
                $out[$idPac] = true;

                continue;
            }

            if ($ncTipo > 0 && $tipo === $ncTipo) {
                continue;
            }

            if ($tipo <= 0) {
                continue;
            }

            // Factura (u otro comprobante AFIP) sin NC asociada.
            if (! isset($anuladas[(int) $row->id])) {
                $out[$idPac] = true;
            }
        }

        return $out;
    }
}
