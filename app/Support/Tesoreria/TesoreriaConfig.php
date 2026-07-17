<?php

namespace App\Support\Tesoreria;

use App\Models\Concepto;
use Illuminate\Support\Facades\Schema;

/**
 * Variantes de tesorería por tenant (`config('tenant.tesoreria.implementacion')`).
 */
final class TesoreriaConfig
{
    public const IMPL_PACIENTES = 'tesoreria_pacientes';

    public const IMPL_MOVIMIENTOS = 'tesoreria_movimientos';

    public static function implementacion(): string
    {
        $valor = (string) config('tenant.tesoreria.implementacion', self::IMPL_PACIENTES);

        return in_array($valor, [self::IMPL_PACIENTES, self::IMPL_MOVIMIENTOS], true)
            ? $valor
            : self::IMPL_PACIENTES;
    }

    public static function usaMovimientos(): bool
    {
        return self::implementacion() === self::IMPL_MOVIMIENTOS;
    }

    public static function usaPacientes(): bool
    {
        return ! self::usaMovimientos();
    }

    /** Días hacia atrás para el selector “Fecha de los Protocolos a Cargar”. */
    public static function diasProtocolos(): int
    {
        return max(1, (int) config('tenant.tesoreria.movimientos.dias_protocolos', 7));
    }

    public static function nombreConceptoIngresosDiarios(): string
    {
        return (string) config('tenant.tesoreria.movimientos.concepto_ingresos_diarios', 'Ingresos Diarios');
    }

    public static function nombreConceptoCadeteria(): string
    {
        return (string) config('tenant.tesoreria.movimientos.concepto_cadeteria', 'Cadetería');
    }

    public static function idConceptoIngresosDiarios(): ?int
    {
        return self::idConceptoPorNombre(self::nombreConceptoIngresosDiarios());
    }

    public static function idConceptoCadeteria(): ?int
    {
        return self::idConceptoPorNombre(self::nombreConceptoCadeteria());
    }

    public static function esConceptoIngresosDiarios(?int $idConcepto): bool
    {
        $id = self::idConceptoIngresosDiarios();

        return $id !== null && $idConcepto !== null && (int) $idConcepto === $id;
    }

    public static function esConceptoCadeteria(?int $idConcepto): bool
    {
        $id = self::idConceptoCadeteria();

        return $id !== null && $idConcepto !== null && (int) $idConcepto === $id;
    }

    private static function idConceptoPorNombre(string $nombre): ?int
    {
        if ($nombre === '' || ! Schema::hasTable('conceptos')) {
            return null;
        }

        $id = Concepto::query()
            ->whereRaw('LOWER(concepto) = ?', [mb_strtolower(trim($nombre))])
            ->value('id');

        return $id !== null ? (int) $id : null;
    }
}
