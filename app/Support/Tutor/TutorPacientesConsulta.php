<?php

namespace App\Support\Tutor;

use App\Models\Paciente;
use App\Support\Protocolos\PacienteListadoOrden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Protocolos del tutor: mismo DNI, solo estudios (tipo protocolo) en Final o Final/Env.
 */
final class TutorPacientesConsulta
{
    /**
     * @return Builder<Paciente>
     */
    public static function alcance(string $dni): Builder
    {
        $dni = TutorAcceso::dni($dni);
        $query = Paciente::query()
            ->where('pacientes.tipoRegistro', Paciente::TIPO_PROTOCOLO);

        if ($dni === '' || ! Schema::hasColumn('pacientes', 'dni')) {
            return $query->whereRaw('0 = 1');
        }

        return $query
            ->where('pacientes.dni', $dni)
            ->whereIn('pacientes.estado', TutorAcceso::estadosVisibles());
    }

    public static function tieneEstudios(string $dni): bool
    {
        return self::alcance($dni)->exists();
    }

    public static function nombreTutor(string $dni): string
    {
        $nombre = self::alcance($dni)
            ->orderByDesc('pacientes.fechhoy')
            ->orderByDesc('pacientes.idPacientes')
            ->value('propietario');

        return trim((string) $nombre);
    }

    public static function enAlcance(int $idPacientes, string $dni): ?Paciente
    {
        if ($idPacientes <= 0) {
            return null;
        }

        return self::alcance($dni)
            ->where('pacientes.idPacientes', $idPacientes)
            ->first();
    }

    /**
     * @return Builder<Paciente>
     */
    public static function listado(string $dni, string $busqueda, string $fechaDesde, string $fechaHasta): Builder
    {
        $term = trim($busqueda);
        if (mb_strlen($term) > 80) {
            $term = mb_substr($term, 0, 80);
        }

        $desde = self::fechaYmd($fechaDesde);
        $hasta = self::fechaYmd($fechaHasta);
        if ($desde !== null && $hasta !== null && $desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return self::alcance($dni)
            ->with(['especie', 'raza'])
            ->when($desde !== null, fn (Builder $q) => $q->whereDate('pacientes.fechhoy', '>=', $desde))
            ->when($hasta !== null, fn (Builder $q) => $q->whereDate('pacientes.fechhoy', '<=', $hasta))
            ->when($term !== '', function (Builder $q) use ($term) {
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('pacientes.nombreProtocolo', 'like', '%'.$term.'%')
                        ->orWhere('pacientes.nombre', 'like', '%'.$term.'%')
                        ->orWhere('pacientes.propietario', 'like', '%'.$term.'%');
                });
            })
            ->tap(fn (Builder $q) => PacienteListadoOrden::aplicar($q));
    }

    public static function fechaYmd(string $value): ?string
    {
        $value = trim($value);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $fecha = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        if ($fecha === false || $fecha->format('Y-m-d') !== $value) {
            return null;
        }

        return $value;
    }
}
