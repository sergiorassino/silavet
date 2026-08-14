<?php

namespace App\Support\Cliente;

use App\Models\Determinacion;
use App\Models\Paciente;
use App\Support\PrecioInput;
use App\Support\Precios\DeterminacionImportes;
use Illuminate\Support\Facades\Schema;

/**
 * Detalle de solo lectura de determinaciones pedidas de un protocolo (autogestión).
 */
final class DetalleDeterminacionesConsulta
{
    /**
     * @return array{
     *     cliente: string,
     *     paciente: string,
     *     protocolo: string,
     *     filas: list<array{nombre: string, neto: float, descuento: float, precio: float, neto_fmt: string, descuento_fmt: string, precio_fmt: string}>,
     *     total_neto: float,
     *     total_descuento: float,
     *     total_con_descuento: float,
     *     total_neto_fmt: string,
     *     total_descuento_fmt: string,
     *     total_con_descuento_fmt: string,
     * }
     */
    public static function armar(Paciente $paciente, string $busqueda = ''): array
    {
        $tieneNeto = Schema::hasColumn('determinaciones', 'neto');

        $registros = Determinacion::query()
            ->with('tipodeterminacion')
            ->where('idPacientes', (int) $paciente->idPacientes)
            ->orderBy('idDeterminaciones')
            ->get();

        $filtro = mb_strtolower(trim($busqueda));
        $filas = [];
        $totalNeto = 0.0;
        $totalDescuento = 0.0;
        $totalPrecio = 0.0;

        foreach ($registros as $registro) {
            $nombre = trim((string) ($registro->tipodeterminacion?->nombre ?? ''));
            if ($nombre === '') {
                $nombre = '—';
            }

            if ($filtro !== '' && ! str_contains(mb_strtolower($nombre), $filtro)) {
                continue;
            }

            $neto = $tieneNeto ? (float) ($registro->neto ?? 0) : 0.0;
            $importes = DeterminacionImportes::resolver(
                $neto,
                (float) ($registro->precio ?? 0),
                (float) ($registro->descuento ?? 0),
            );

            $totalNeto += $importes['neto'];
            $totalDescuento += $importes['descuento'];
            $totalPrecio += $importes['precio'];

            $filas[] = [
                'nombre' => $nombre,
                'neto' => $importes['neto'],
                'descuento' => $importes['descuento'],
                'precio' => $importes['precio'],
                'neto_fmt' => PrecioInput::format($importes['neto']),
                'descuento_fmt' => PrecioInput::format($importes['descuento']),
                'precio_fmt' => PrecioInput::format($importes['precio']),
            ];
        }

        $totalNeto = round($totalNeto, 2);
        $totalDescuento = round($totalDescuento, 2);
        $totalPrecio = round($totalPrecio, 2);

        $paciente->loadMissing('cliente');

        return [
            'cliente' => trim((string) ($paciente->cliente?->nombre ?? '')) ?: '—',
            'paciente' => trim((string) ($paciente->nombre ?? '')) ?: '—',
            'protocolo' => trim((string) ($paciente->nombreProtocolo ?? '')) ?: '—',
            'filas' => $filas,
            'total_neto' => $totalNeto,
            'total_descuento' => $totalDescuento,
            'total_con_descuento' => $totalPrecio,
            'total_neto_fmt' => PrecioInput::format($totalNeto),
            'total_descuento_fmt' => PrecioInput::format($totalDescuento),
            'total_con_descuento_fmt' => PrecioInput::format($totalPrecio),
        ];
    }

    public static function pacienteEnAlcance(int $idPacientes): ?Paciente
    {
        $ctx = labCtx();
        if (! $ctx->esCliente() || ! $ctx->idClientes) {
            return null;
        }

        $paciente = Paciente::query()
            ->with('cliente')
            ->where('idPacientes', $idPacientes)
            ->where('idClientes', $ctx->idClientes)
            ->first();

        if ($paciente === null || $paciente->esPagoGlobal() || $paciente->esEgreso()) {
            return null;
        }

        return $paciente;
    }
}
