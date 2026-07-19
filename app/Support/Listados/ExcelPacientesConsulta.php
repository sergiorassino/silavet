<?php

namespace App\Support\Listados;

use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ExcelPacientesConsulta
{
    /**
     * @param  array{fechaDesde?: string, fechaHasta?: string}  $filtros
     * @return Collection<int, object>
     */
    public static function listado(array $filtros): Collection
    {
        $desde = trim((string) ($filtros['fechaDesde'] ?? ''));
        $hasta = trim((string) ($filtros['fechaHasta'] ?? ''));

        $ctx = labCtx();
        $tieneObsInterna = Schema::hasColumn('pacientes', 'obsInterna');

        $pacientes = Paciente::query()
            ->with([
                'cliente:idClientes,nombre',
                'especie:idEspecies,nombre',
                'raza:idRazas,nombre',
            ])
            ->where('pacientes.tipoRegistro', Paciente::TIPO_PROTOCOLO)
            ->when($ctx->esCliente() && $ctx->idClientes, fn ($q) => $q->where('pacientes.idClientes', $ctx->idClientes))
            ->when($desde !== '', fn ($q) => $q->whereDate('pacientes.fechhoy', '>=', Carbon::parse($desde)->toDateString()))
            ->when($hasta !== '', fn ($q) => $q->whereDate('pacientes.fechhoy', '<=', Carbon::parse($hasta)->toDateString()))
            ->orderBy('pacientes.fechhoy')
            ->orderBy('pacientes.nombreProtocolo')
            ->orderBy('pacientes.idPacientes')
            ->get();

        $determinacionesPorPaciente = self::determinacionesPorPaciente(
            $pacientes->pluck('idPacientes')->map(fn ($id) => (int) $id)->all()
        );

        return $pacientes->map(function (Paciente $paciente) use ($tieneObsInterna, $determinacionesPorPaciente) {
            $id = (int) $paciente->idPacientes;

            return (object) [
                'idPacientes' => $id,
                'cliente' => trim((string) ($paciente->cliente?->nombre ?? '')),
                'especie' => trim((string) ($paciente->especie?->nombre ?? '')),
                'raza' => trim((string) ($paciente->raza?->nombre ?? '')),
                'sexo' => trim((string) ($paciente->sexo ?? '')),
                'edad' => trim((string) ($paciente->edad ?? '')),
                'fechhoy' => $paciente->fechhoy?->format('Y-m-d') ?? '',
                'nombreProtocolo' => trim((string) ($paciente->nombreProtocolo ?? '')),
                'nombre' => trim((string) ($paciente->nombre ?? '')),
                'propietario' => trim((string) ($paciente->propietario ?? '')),
                'estado' => trim((string) ($paciente->estado ?? '')),
                'precio' => round((float) ($paciente->precio ?? 0), 2),
                'observaciones' => trim((string) ($paciente->observaciones ?? '')),
                'obsInterna' => $tieneObsInterna
                    ? trim((string) ($paciente->obsInterna ?? ''))
                    : '',
                'determinaciones' => $determinacionesPorPaciente[$id] ?? '',
            ];
        });
    }

    public static function etiquetaPeriodo(?string $desde, ?string $hasta): string
    {
        $desde = trim((string) $desde);
        $hasta = trim((string) $hasta);

        if ($desde === '' && $hasta === '') {
            return 'Sin período';
        }

        $d = $desde !== '' ? Carbon::parse($desde)->format('d/m/Y') : 'Inicio';
        $h = $hasta !== '' ? Carbon::parse($hasta)->format('d/m/Y') : 'Hoy';

        return $d.' — '.$h;
    }

    /**
     * @param  list<int>  $idsPacientes
     * @return array<int, string>
     */
    private static function determinacionesPorPaciente(array $idsPacientes): array
    {
        if ($idsPacientes === [] || ! Schema::hasTable('renglones') || ! Schema::hasTable('tipodeterminaciones')) {
            return [];
        }

        $filas = DB::table('renglones as r')
            ->join('tipodeterminaciones as td', 'r.idTipodeterminacion', '=', 'td.idTipodeterminaciones')
            ->whereIn('r.idPacientes', $idsPacientes)
            ->whereNotNull('r.idTipodeterminacion')
            ->where('r.idTipodeterminacion', '>', 0)
            ->orderBy('r.idPacientes')
            ->orderBy('td.orden')
            ->orderBy('td.nombre')
            ->select('r.idPacientes', 'td.nombre')
            ->get();

        $map = [];
        foreach ($filas as $fila) {
            $id = (int) $fila->idPacientes;
            $nombre = trim((string) ($fila->nombre ?? ''));
            if ($nombre === '') {
                continue;
            }
            if (! isset($map[$id])) {
                $map[$id] = [];
            }
            if (! in_array($nombre, $map[$id], true)) {
                $map[$id][] = $nombre;
            }
        }

        $resultado = [];
        foreach ($map as $id => $nombres) {
            $resultado[$id] = implode(', ', $nombres);
        }

        return $resultado;
    }
}
