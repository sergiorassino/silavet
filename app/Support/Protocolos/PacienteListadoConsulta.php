<?php

namespace App\Support\Protocolos;

use App\Livewire\Protocolos\PacienteIndex;
use App\Models\Paciente;
use App\Support\Resultados\ResultadosEstadosCatalog;
use App\Support\Tesoreria\TesoreriaConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Misma consulta que la grilla de PacienteIndex (staff y autogestión).
 */
final class PacienteListadoConsulta
{
    /**
     * @param  array{
     *     vista?: string,
     *     fechaVista?: string,
     *     fechaDesde?: string,
     *     fechaHasta?: string,
     *     busqueda?: string,
     *     filtroEstado?: string
     * }  $filtros
     * @return Builder<Paciente>
     */
    public static function query(array $filtros): Builder
    {
        $ctx = labCtx();
        $vista = trim((string) ($filtros['vista'] ?? ''));
        $vista = $vista === PacienteIndex::VISTA_HISTORIAL
            ? PacienteIndex::VISTA_HISTORIAL
            : PacienteIndex::VISTA_HOY;
        $term = trim((string) ($filtros['busqueda'] ?? ''));
        $filtroEstado = self::filtroEstadoEfectivo((string) ($filtros['filtroEstado'] ?? ''));

        $with = ['cliente', 'especie', 'raza', 'medioDePago'];
        if (Schema::hasTable('notificaciones')) {
            $with[] = 'notificacion';
        }

        return Paciente::query()
            ->with($with)
            ->tap(fn ($q) => self::aplicarFiltroTipoRegistro($q))
            ->when(
                $ctx->esCliente() && $ctx->idClientes,
                function ($q) use ($ctx) {
                    $q->where('pacientes.idClientes', $ctx->idClientes);
                }
            )
            ->when($vista === PacienteIndex::VISTA_HOY, function ($q) use ($filtros) {
                $q->whereDate('pacientes.fechhoy', self::fechaVistaEfectiva((string) ($filtros['fechaVista'] ?? '')));
            })
            ->when($vista === PacienteIndex::VISTA_HISTORIAL, function ($q) use ($filtros) {
                $desde = self::fechaYmd((string) ($filtros['fechaDesde'] ?? ''));
                $hasta = self::fechaYmd((string) ($filtros['fechaHasta'] ?? ''));
                if ($desde !== null) {
                    $q->whereDate('pacientes.fechhoy', '>=', $desde);
                }
                if ($hasta !== null) {
                    $q->whereDate('pacientes.fechhoy', '<=', $hasta);
                }
            })
            ->when($term !== '', function ($q) use ($term, $ctx) {
                $q->where(function ($inner) use ($term, $ctx) {
                    $inner->where('pacientes.nombreProtocolo', 'like', "%{$term}%")
                        ->orWhere('pacientes.nombre', 'like', "%{$term}%")
                        ->orWhere('pacientes.propietario', 'like', "%{$term}%");
                    if (! ($ctx->esCliente() && $ctx->idClientes)) {
                        $inner->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$term}%"));
                    }
                });
            })
            ->when($filtroEstado === PacienteIndex::FILTRO_PENDIENTES, function ($q) {
                $estados = [
                    ResultadosEstadosCatalog::EN_PROC,
                    ResultadosEstadosCatalog::PARCIAL,
                ];
                $q->where(function ($inner) use ($estados) {
                    $inner->whereIn('pacientes.estado', $estados)
                        ->orWhereNull('pacientes.estado')
                        ->orWhere('pacientes.estado', '');
                });
            })
            ->when($filtroEstado === PacienteIndex::FILTRO_LISTOS, function ($q) {
                $q->whereIn('pacientes.estado', ResultadosEstadosCatalog::estadosFinalizados());
            })
            ->ordenListado();
    }

    /**
     * Filtro de `tipoRegistro` del listado / alcance:
     * - labvetciudad (`tesoreria_pacientes`): sin filtro (protocolos legacy suelen ser 0).
     * - Autogestión cliente (`tesoreria_movimientos`): protocolos (1) + pagos globales (2).
     * - Staff con `pago_global`: protocolos (1) + pagos globales (2).
     * - Staff NeoLab sin el flag: solo protocolos (1); ingresos/egresos en Tesorería.
     *
     * @param  Builder<Paciente>  $query
     */
    public static function aplicarFiltroTipoRegistro($query): void
    {
        if (TesoreriaConfig::usaPacientes()) {
            return;
        }

        if (labCtx()->esCliente() || TesoreriaConfig::pagoGlobalHabilitado()) {
            $query->whereIn('pacientes.tipoRegistro', [
                Paciente::TIPO_PROTOCOLO,
                Paciente::TIPO_INGRESO,
            ]);

            return;
        }

        $query->where('pacientes.tipoRegistro', Paciente::TIPO_PROTOCOLO);
    }

    public static function filtroEstadoEfectivo(string $filtro): string
    {
        $filtro = trim($filtro);

        return in_array($filtro, [PacienteIndex::FILTRO_PENDIENTES, PacienteIndex::FILTRO_LISTOS], true)
            ? $filtro
            : '';
    }

    public static function fechaVistaEfectiva(string $fecha): string
    {
        return self::fechaYmd($fecha) ?? now()->toDateString();
    }

    public static function fechaYmd(string $valor): ?string
    {
        $valor = trim($valor);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1 ? $valor : null;
    }
}
