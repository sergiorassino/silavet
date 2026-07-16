<?php

namespace App\Support\Listados;

use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ListadoEstadisticoPacientesConsulta
{
    /**
     * @param  array{
     *     idClientes?: int|null,
     *     paciente?: string,
     *     idEspecies?: int|null,
     *     idRazas?: int|null,
     *     fechaDesde?: string,
     *     fechaHasta?: string,
     *     agruparPorCliente?: bool
     * }  $filtros
     * @return LengthAwarePaginator<int, object>
     */
    public static function paginado(array $filtros, int $porPagina): LengthAwarePaginator
    {
        return self::queryBase($filtros)
            ->paginate($porPagina)
            ->through(fn (Paciente $paciente) => self::mapearFila($paciente));
    }

    /**
     * @param  array{
     *     idClientes?: int|null,
     *     paciente?: string,
     *     idEspecies?: int|null,
     *     idRazas?: int|null,
     *     fechaDesde?: string,
     *     fechaHasta?: string,
     *     agruparPorCliente?: bool
     * }  $filtros
     * @return Collection<int, object>
     */
    public static function listado(array $filtros): Collection
    {
        return self::queryBase($filtros)
            ->get()
            ->map(fn (Paciente $paciente) => self::mapearFila($paciente));
    }

    /**
     * @param  iterable<object>  $filas
     * @return array{total_precio: float, total_pagado: float, cantidad: int}
     */
    public static function resumen(iterable $filas): array
    {
        $totalPrecio = 0.0;
        $totalPagado = 0.0;
        $cantidad = 0;

        foreach ($filas as $fila) {
            $totalPrecio += round((float) ($fila->precio ?? 0), 2);
            $totalPagado += round((float) ($fila->pagado ?? 0), 2);
            $cantidad++;
        }

        return [
            'total_precio' => round($totalPrecio, 2),
            'total_pagado' => round($totalPagado, 2),
            'cantidad' => $cantidad,
        ];
    }

    /**
     * Agrupa filas consecutivas por cliente (para encabezados de grupo en UI/PDF).
     *
     * @param  iterable<object>  $filas
     * @return list<array{tipo: 'grupo'|'fila', cliente?: string, idClientes?: int, fila?: object, subtotal_precio?: float, subtotal_pagado?: float, cantidad?: int}>
     */
    public static function bloquesAgrupados(iterable $filas): array
    {
        $grupos = [];
        foreach ($filas as $fila) {
            $id = (int) ($fila->idClientes ?? 0);
            if (! isset($grupos[$id])) {
                $grupos[$id] = [
                    'cliente' => (string) ($fila->cliente ?? 'Sin cliente'),
                    'idClientes' => $id,
                    'filas' => [],
                ];
            }
            $grupos[$id]['filas'][] = $fila;
        }

        $bloques = [];
        foreach ($grupos as $grupo) {
            $subPrecio = 0.0;
            $subPagado = 0.0;
            foreach ($grupo['filas'] as $fila) {
                $subPrecio += round((float) ($fila->precio ?? 0), 2);
                $subPagado += round((float) ($fila->pagado ?? 0), 2);
            }

            $bloques[] = [
                'tipo' => 'grupo',
                'cliente' => $grupo['cliente'],
                'idClientes' => $grupo['idClientes'],
                'subtotal_precio' => round($subPrecio, 2),
                'subtotal_pagado' => round($subPagado, 2),
                'cantidad' => count($grupo['filas']),
            ];

            foreach ($grupo['filas'] as $fila) {
                $bloques[] = ['tipo' => 'fila', 'fila' => $fila];
            }
        }

        return $bloques;
    }

    public static function formatearMoneda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }

    public static function etiquetaPeriodo(?string $desde, ?string $hasta): string
    {
        $desde = trim((string) $desde);
        $hasta = trim((string) $hasta);

        if ($desde === '' && $hasta === '') {
            return 'Todo el historial';
        }

        $d = $desde !== '' ? Carbon::parse($desde)->format('d/m/Y') : 'Inicio';
        $h = $hasta !== '' ? Carbon::parse($hasta)->format('d/m/Y') : 'Hoy';

        return $d.' — '.$h;
    }

    /**
     * @param  array{
     *     idClientes?: int|null,
     *     paciente?: string,
     *     idEspecies?: int|null,
     *     idRazas?: int|null,
     *     fechaDesde?: string,
     *     fechaHasta?: string,
     *     agruparPorCliente?: bool
     * }  $filtros
     * @return Builder<Paciente>
     */
    private static function queryBase(array $filtros): Builder
    {
        $ctx = labCtx();
        $idClientes = isset($filtros['idClientes']) && $filtros['idClientes'] !== null && $filtros['idClientes'] !== ''
            ? (int) $filtros['idClientes']
            : null;
        $paciente = trim((string) ($filtros['paciente'] ?? ''));
        $idEspecies = isset($filtros['idEspecies']) && $filtros['idEspecies'] !== null && $filtros['idEspecies'] !== ''
            ? (int) $filtros['idEspecies']
            : null;
        $idRazas = isset($filtros['idRazas']) && $filtros['idRazas'] !== null && $filtros['idRazas'] !== ''
            ? (int) $filtros['idRazas']
            : null;
        $desde = trim((string) ($filtros['fechaDesde'] ?? ''));
        $hasta = trim((string) ($filtros['fechaHasta'] ?? ''));
        $agrupar = (bool) ($filtros['agruparPorCliente'] ?? false);

        if ($ctx->esCliente() && $ctx->idClientes) {
            $idClientes = (int) $ctx->idClientes;
        }

        $query = Paciente::query()
            ->with([
                'cliente:idClientes,nombre',
                'especie:idEspecies,nombre',
                'raza:idRazas,nombre',
            ])
            ->where('pacientes.tipoRegistro', Paciente::TIPO_PROTOCOLO)
            ->when($idClientes !== null, fn ($q) => $q->where('pacientes.idClientes', $idClientes))
            ->when($ctx->esCliente() && $ctx->idClientes, fn ($q) => $q->where('pacientes.idClientes', $ctx->idClientes))
            ->when($paciente !== '', function ($q) use ($paciente) {
                $q->where(function ($inner) use ($paciente) {
                    $inner->where('pacientes.nombre', 'like', "%{$paciente}%")
                        ->orWhere('pacientes.propietario', 'like', "%{$paciente}%")
                        ->orWhere('pacientes.nombreProtocolo', 'like', "%{$paciente}%");
                });
            })
            ->when($idEspecies !== null, fn ($q) => $q->where('pacientes.idEspecies', $idEspecies))
            ->when($idRazas !== null, fn ($q) => $q->where('pacientes.idRazas', $idRazas))
            ->when($desde !== '', fn ($q) => $q->whereDate('pacientes.fechhoy', '>=', Carbon::parse($desde)->toDateString()))
            ->when($hasta !== '', fn ($q) => $q->whereDate('pacientes.fechhoy', '<=', Carbon::parse($hasta)->toDateString()));

        if ($agrupar) {
            $query->leftJoin('clientes', 'clientes.idClientes', '=', 'pacientes.idClientes')
                ->select('pacientes.*')
                ->orderBy('clientes.nombre')
                ->orderByDesc('pacientes.fechhoy')
                ->orderBy('pacientes.nombreProtocolo')
                ->orderByDesc('pacientes.idPacientes');
        } else {
            $query->orderByDesc('pacientes.fechhoy')
                ->orderBy('pacientes.nombreProtocolo')
                ->orderByDesc('pacientes.idPacientes');
        }

        return $query;
    }

    private static function mapearFila(Paciente $paciente): object
    {
        return (object) [
            'idPacientes' => (int) $paciente->idPacientes,
            'idClientes' => (int) ($paciente->idClientes ?? 0),
            'cliente' => trim((string) ($paciente->cliente?->nombre ?? '')),
            'especie' => trim((string) ($paciente->especie?->nombre ?? '')),
            'raza' => trim((string) ($paciente->raza?->nombre ?? '')),
            'fechhoy' => $paciente->fechhoy?->format('Y-m-d') ?? '',
            'nombreProtocolo' => trim((string) ($paciente->nombreProtocolo ?? '')),
            'nombre' => trim((string) ($paciente->nombre ?? '')),
            'propietario' => trim((string) ($paciente->propietario ?? '')),
            'estado' => trim((string) ($paciente->estado ?? '')),
            'precio' => round((float) ($paciente->precio ?? 0), 2),
            'pagado' => round((float) ($paciente->pagado ?? 0), 2),
        ];
    }
}
