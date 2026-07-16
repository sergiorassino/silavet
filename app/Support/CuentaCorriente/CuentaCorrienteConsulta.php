<?php

namespace App\Support\CuentaCorriente;

use App\Models\Cliente;
use App\Models\Paciente;
use App\Support\Precios\PrecioDeterminacionResolver;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CuentaCorrienteConsulta
{
    public static function saldoProtocolo(Paciente $paciente): float
    {
        $neto = PrecioDeterminacionResolver::neto(
            (float) ($paciente->precio ?? 0),
            (float) ($paciente->descuento ?? 0),
        );

        return round(max(0, $neto - (float) ($paciente->pagado ?? 0)), 2);
    }

    public static function importeNetoProtocolo(Paciente $paciente): float
    {
        return PrecioDeterminacionResolver::neto(
            (float) ($paciente->precio ?? 0),
            (float) ($paciente->descuento ?? 0),
        );
    }

    /**
     * @return LengthAwarePaginator<Cliente&object{saldo_total: float}>
     */
    public static function clientesPaginados(string $busqueda, int $porPagina): LengthAwarePaginator
    {
        return self::queryClientesConSaldo($busqueda)
            ->paginate($porPagina);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Cliente&object{saldo_total: float}>
     */
    public static function clientesListado(string $busqueda): Collection
    {
        return self::queryClientesConSaldo($busqueda)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Cliente>
     */
    private static function queryClientesConSaldo(string $busqueda)
    {
        $term = trim($busqueda);
        $expresionSaldo = self::expresionSqlSaldoProtocolo('p');

        $saldoSubquery = DB::table('pacientes as p')
            ->select('p.idClientes')
            ->selectRaw("SUM({$expresionSaldo}) as saldo_total")
            ->where('p.tipoRegistro', '!=', Paciente::TIPO_EGRESO)
            ->groupBy('p.idClientes');

        return Cliente::query()
            ->leftJoinSub($saldoSubquery, 'saldos', 'saldos.idClientes', '=', 'clientes.idClientes')
            ->select('clientes.*')
            ->selectRaw('COALESCE(saldos.saldo_total, 0) as saldo_total')
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('clientes.nombre', 'like', "%{$term}%")
                        ->orWhere('clientes.direccion', 'like', "%{$term}%")
                        ->orWhere('clientes.telefono1', 'like', "%{$term}%")
                        ->orWhere('clientes.telefono2', 'like', "%{$term}%");
                });
            })
            ->orderBy('clientes.nombre');
    }

    public static function movimientoCuentaCorriente(Paciente $paciente): float
    {
        return self::movimientoNetoProtocolo($paciente);
    }

    /**
     * Variación de saldo (misma regla que NeoLab cuentaCorrientePacientes):
     * precio − pagado sobre los campos guardados en pacientes.
     *
     * No se reaplica descuento ni lógica de neto: cada laboratorio ya deja en
     * precio/pagado el importe que usa su modelo de precios.
     * En pagos (tipoRegistro = 2) se toma el importe cobrado aunque esté solo en precio.
     */
    public static function movimientoNetoProtocolo(Paciente $paciente): float
    {
        if ($paciente->esIngreso()) {
            return round(-$paciente->importePagadoMovimiento(), 2);
        }

        return round(
            (float) ($paciente->precio ?? 0) - (float) ($paciente->pagado ?? 0),
            2
        );
    }

    /**
     * Orden de acumulación de saldo: inverso exacto del listado
     * ({@see Paciente::scopeOrdenAcumulacionSaldo}).
     * La primera fila del listado (protocolos y luego pagos, días recientes
     * primero) queda con el saldo actual de la cuenta.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Paciente>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Paciente>
     */
    public static function aplicarOrdenCalculoSaldo($query)
    {
        return $query->ordenAcumulacionSaldo();
    }

    /**
     * Orden de pantalla: {@see Paciente::scopeOrdenListado}
     * (días DESC; dentro del día, protocolos/pacientes y luego pagos).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Paciente>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Paciente>
     */
    public static function aplicarOrdenListado($query)
    {
        return $query->ordenListado();
    }

    /**
     * Saldo acumulado al cierre de cada protocolo (orden inverso al listado).
     * La clave del primer registro del listado corresponde al saldo actual.
     *
     * @return array<int, float> clave idPacientes
     */
    public static function mapaSaldoAcumuladoPorProtocolo(int $idClientes): array
    {
        $protocolos = self::aplicarOrdenCalculoSaldo(
            Paciente::query()
                ->where('idClientes', $idClientes)
                ->where('tipoRegistro', '!=', Paciente::TIPO_EGRESO)
        )->get(self::columnasMovimientoSaldo(['idPacientes']));

        $mapa = [];
        $running = 0.0;

        foreach ($protocolos as $protocolo) {
            $running = round($running + self::movimientoNetoProtocolo($protocolo), 2);
            $mapa[(int) $protocolo->idPacientes] = $running;
        }

        return $mapa;
    }

    /**
     * Saldo de la cuenta al cierre de cada fecha (todo el historial del cliente).
     *
     * @return array<string, float> clave Y-m-d
     */
    public static function mapaSaldoPorCierreDia(int $idClientes): array
    {
        $protocolos = self::aplicarOrdenCalculoSaldo(
            Paciente::query()
                ->where('idClientes', $idClientes)
                ->where('tipoRegistro', '!=', Paciente::TIPO_EGRESO)
        )->get(self::columnasMovimientoSaldo(['idPacientes', 'fechhoy']));

        $mapa = [];
        $running = 0.0;

        foreach ($protocolos as $protocolo) {
            $fecha = $protocolo->fechhoy?->format('Y-m-d') ?? '';
            if ($fecha === '') {
                continue;
            }

            $running = round($running + self::movimientoNetoProtocolo($protocolo), 2);

            $mapa[$fecha] = $running;
        }

        return $mapa;
    }

    public static function saldoAlCierreDia(int $idClientes, string $fecha): float
    {
        $fecha = trim($fecha);
        if ($fecha === '') {
            return 0.0;
        }

        return self::mapaSaldoPorCierreDia($idClientes)[$fecha] ?? 0.0;
    }

    /**
     * Saldo acumulado de todos los protocolos anteriores a la fecha indicada.
     * Devuelve null si no hay fecha desde (sin filtro de inicio).
     */
    public static function saldoAnteriorAFecha(int $idClientes, ?string $fechaDesde): ?float
    {
        $desde = trim((string) $fechaDesde);
        if ($desde === '') {
            return null;
        }

        $fechaCorte = Carbon::parse($desde)->toDateString();

        $protocolos = self::aplicarOrdenCalculoSaldo(
            Paciente::query()
                ->where('idClientes', $idClientes)
                ->where('tipoRegistro', '!=', Paciente::TIPO_EGRESO)
                ->whereDate('fechhoy', '<', $fechaCorte)
        )->get(self::columnasMovimientoSaldo());

        $running = 0.0;

        foreach ($protocolos as $protocolo) {
            $running = round($running + self::movimientoNetoProtocolo($protocolo), 2);
        }

        return $running;
    }

    /**
     * @return Collection<int, object{
     *   idPacientes: int,
     *   idClientes: int,
     *   especie: string,
     *   raza: string,
     *   fechhoy: string,
     *   nombreProtocolo: string,
     *   nombre: string,
     *   propietario: string,
     *   estado: string,
     *   precio: float,
     *   pagado: float,
     *   saldo: float,
     *   esPagoGlobal: bool,
     * }>
     */
    public static function protocolosCliente(int $idClientes, ?string $fechaDesde = null, ?string $fechaHasta = null): Collection
    {
        $desde = trim((string) $fechaDesde);
        $hasta = trim((string) $fechaHasta);

        $protocolos = self::aplicarOrdenListado(
            Paciente::query()
                ->with(['especie:idEspecies,nombre', 'raza:idRazas,nombre'])
                ->where('idClientes', $idClientes)
                ->where('tipoRegistro', '!=', Paciente::TIPO_EGRESO)
                ->when($desde !== '', fn ($q) => $q->whereDate('fechhoy', '>=', Carbon::parse($desde)->toDateString()))
                ->when($hasta !== '', fn ($q) => $q->whereDate('fechhoy', '<=', Carbon::parse($hasta)->toDateString()))
        )->get();

        $saldosAcumulados = self::mapaSaldoAcumuladoPorProtocolo($idClientes);

        return $protocolos->map(function (Paciente $paciente) use ($saldosAcumulados) {
            $fecha = $paciente->fechhoy?->format('Y-m-d') ?? '';
            $esPagoGlobal = $paciente->esPagoGlobal();

            return (object) [
                'idPacientes' => (int) $paciente->idPacientes,
                'idClientes' => (int) ($paciente->idClientes ?? 0),
                'especie' => trim((string) ($paciente->especie?->nombre ?? '')),
                'raza' => trim((string) ($paciente->raza?->nombre ?? '')),
                'fechhoy' => $fecha,
                'nombreProtocolo' => trim((string) ($paciente->nombreProtocolo ?? '')),
                'nombre' => trim((string) ($paciente->nombre ?? '')),
                'propietario' => trim((string) ($paciente->propietario ?? '')),
                'estado' => trim((string) ($paciente->estado ?? '')),
                // Campos crudos como NeoLab (el saldo no reaplica descuentos).
                'precio' => $esPagoGlobal ? 0.0 : round((float) ($paciente->precio ?? 0), 2),
                'pagado' => $esPagoGlobal
                    ? $paciente->importePagadoMovimiento()
                    : round((float) ($paciente->pagado ?? 0), 2),
                'saldo' => $saldosAcumulados[(int) $paciente->idPacientes] ?? 0.0,
                'esPagoGlobal' => $esPagoGlobal,
            ];
        });
    }

    /**
     * @param  Collection<int, object>  $filas
     * @return array{total_precio: float, total_pagado: float, cantidad: int}
     */
    public static function resumenProtocolos(Collection $filas): array
    {
        return [
            'total_precio' => round($filas->sum(fn ($fila) => (float) $fila->precio), 2),
            'total_pagado' => round($filas->sum(fn ($fila) => (float) $fila->pagado), 2),
            'cantidad' => $filas->count(),
        ];
    }

    public static function saldoClienteHoy(int $idClientes): float
    {
        $expresionSaldo = self::expresionSqlSaldoProtocolo('pacientes');

        return round((float) DB::table('pacientes')
            ->where('idClientes', $idClientes)
            ->where('tipoRegistro', '!=', Paciente::TIPO_EGRESO)
            ->selectRaw("COALESCE(SUM({$expresionSaldo}), 0) AS saldo_total")
            ->value('saldo_total'), 2);
    }

    private static function expresionSqlSaldoProtocolo(string $alias): string
    {
        // Misma base que NeoLab (precio − pagado). Pagos: importe en pagado o, legacy, en precio.
        return "CASE
            WHEN COALESCE({$alias}.tipoRegistro, 0) = ".Paciente::TIPO_INGRESO."
                THEN -ABS(COALESCE(NULLIF({$alias}.pagado, 0), {$alias}.precio, 0))
            ELSE COALESCE({$alias}.precio, 0) - COALESCE({$alias}.pagado, 0)
        END";
    }

    /**
     * @param  list<string>  $extra
     * @return list<string>
     */
    private static function columnasMovimientoSaldo(array $extra = []): array
    {
        return array_values(array_unique(array_merge(
            $extra,
            ['tipoRegistro', 'precio', 'pagado']
        )));
    }

    public static function etiquetaPeriodo(?string $fechaDesde, ?string $fechaHasta): string
    {
        $desde = trim((string) $fechaDesde);
        $hasta = trim((string) $fechaHasta);

        if ($desde === '' && $hasta === '') {
            return 'Todo el historial';
        }

        if ($desde !== '' && $hasta !== '') {
            return Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y');
        }

        if ($desde !== '') {
            return 'Desde '.Carbon::parse($desde)->format('d/m/Y');
        }

        return 'Hasta '.Carbon::parse($hasta)->format('d/m/Y');
    }

    public static function formatearMoneda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
