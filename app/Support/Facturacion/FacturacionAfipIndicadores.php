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

        $idsStr = array_map(static fn (int $id): string => (string) $id, $ids);

        $columnas = ['id', 'idPacientes', 'CbteTipo'];
        if (CompAfip::tieneColumnaAsoc()) {
            $columnas[] = 'idCompAfipAsoc';
        }

        $rows = CompAfip::query()
            ->whereIn('idPacientes', $idsStr)
            ->orderBy('id')
            ->get($columnas);

        return self::mapaVigentePorClave($rows, 'idPacientes');
    }

    /**
     * Ids de movimientos de caja con factura vigente o comanda.
     *
     * @param  list<int|string>  $idsMovimientos
     * @return array<int, true>
     */
    public static function mapaConEmitidoPorMovimiento(array $idsMovimientos): array
    {
        if (! CompAfip::tieneColumnaMovimientos()) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($id): int => (int) $id,
            $idsMovimientos
        ), static fn (int $id): bool => $id > 0)));

        if ($ids === [] || ! Schema::hasTable('compafip')) {
            return [];
        }

        $columnas = ['id', 'idMovimientos', 'CbteTipo'];
        if (CompAfip::tieneColumnaAsoc()) {
            $columnas[] = 'idCompAfipAsoc';
        }

        $rows = CompAfip::query()
            ->whereIn('idMovimientos', $ids)
            ->orderBy('id')
            ->get($columnas);

        return self::mapaVigentePorClave($rows, 'idMovimientos');
    }

    /**
     * Cronológico por origen: factura abre vigencia; NC la cierra; comanda también marca emitido.
     *
     * @param  \Illuminate\Support\Collection<int, CompAfip>  $rows
     * @return array<int, true>
     */
    private static function mapaVigentePorClave($rows, string $clave): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $tiposNc = self::tiposNotaCredito();
        $comandaTipo = (int) FacturacionAfipConfig::config()['comanda_tipo'];

        $porOrigen = [];
        foreach ($rows as $row) {
            $idOrigen = (int) ($row->{$clave} ?? 0);
            if ($idOrigen <= 0) {
                continue;
            }
            $porOrigen[$idOrigen][] = $row;
        }

        $out = [];
        foreach ($porOrigen as $idOrigen => $lista) {
            $facturaVigente = false;
            $tieneComanda = false;

            foreach ($lista as $row) {
                $tipo = (int) $row->CbteTipo;

                if ($tipo === $comandaTipo || $tipo === FacturacionAfipConfig::CBTE_COMANDA) {
                    $tieneComanda = true;

                    continue;
                }

                if (in_array($tipo, $tiposNc, true)) {
                    $facturaVigente = false;

                    continue;
                }

                if ($tipo > 0) {
                    $facturaVigente = true;
                }
            }

            if ($facturaVigente || $tieneComanda) {
                $out[(int) $idOrigen] = true;
            }
        }

        return $out;
    }

    /**
     * @return list<int>
     */
    private static function tiposNotaCredito(): array
    {
        $cfg = FacturacionAfipConfig::config();
        $emisor = labCtx()->usuario();

        $tiposFactura = array_values(array_unique(array_filter([
            (int) ($cfg['cbte_tipo'] ?? 0),
            $emisor !== null ? (int) ($emisor->CbteTipo ?? 0) : 0,
            1,
            6,
            11,
        ], static fn (int $t): bool => $t > 0)));

        $tipos = [
            (int) ($cfg['nota_credito_tipo'] ?? 0),
            2,
            3,
            7,
            8,
            12,
            13,
        ];

        if ($emisor !== null) {
            $tipos[] = (int) ($emisor->NtaCredTipo ?? 0);
        }

        return array_values(array_unique(array_filter(
            $tipos,
            static fn (int $t): bool => $t > 0 && ! in_array($t, $tiposFactura, true)
        )));
    }
}
