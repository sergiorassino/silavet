<?php

namespace App\Support\Protocolos;

use App\Models\Paciente;
use Illuminate\Database\Eloquent\Builder;

/**
 * Orden del listado de pacientes según `tenant.protocolos.orden_listado`.
 * Vacío: {@see Paciente::scopeOrdenListado} (comportamiento histórico).
 */
final class PacienteListadoOrden
{
    /** @var list<string> */
    private const CAMPOS = ['fechhoy', 'nombreProtocolo'];

    /**
     * @param  Builder<Paciente>  $query
     * @return Builder<Paciente>
     */
    public static function aplicar(Builder $query): Builder
    {
        $criterios = self::criterios();
        if ($criterios === []) {
            return $query->ordenListado();
        }

        $tabla = $query->getModel()->getTable();

        foreach ($criterios as [$campo, $dir]) {
            $query->orderBy($tabla.'.'.$campo, $dir);
        }

        $ultimaDir = $criterios[array_key_last($criterios)][1];
        $query->orderBy($tabla.'.idPacientes', $ultimaDir);

        return $query;
    }

    /**
     * @return list<array{0: string, 1: 'asc'|'desc'}>
     */
    public static function criterios(): array
    {
        $raw = config('tenant.protocolos.orden_listado', []);
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $out = [];
        foreach ($raw as $campo => $dir) {
            $campo = (string) $campo;
            if ($campo === 'fecha') {
                $campo = 'fechhoy';
            }
            if (! in_array($campo, self::CAMPOS, true)) {
                continue;
            }

            $out[] = [$campo, strtolower(trim((string) $dir)) === 'asc' ? 'asc' : 'desc'];
        }

        return $out;
    }
}
