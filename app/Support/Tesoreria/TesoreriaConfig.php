<?php

namespace App\Support\Tesoreria;

use App\Models\Concepto;
use Illuminate\Support\Facades\Schema;

/**
 * Variantes de tesorería por tenant (`config('tenant.tesoreria.implementacion')`).
 *
 * - `tesoreria_movimientos` (default / mayoría): MovimientoIndex — filas en `pacientes`.
 * - `tesoreria_pacientes` (labvetciudad): MovimientosCajaIndex — tabla `movimientos`.
 */
final class TesoreriaConfig
{
    /** Mayoría de labs (alqu, neolab, …): ingresos/egresos en `pacientes`. */
    public const IMPL_MOVIMIENTOS = 'tesoreria_movimientos';

    /** labvetciudad: caja sobre tabla `movimientos`. */
    public const IMPL_PACIENTES = 'tesoreria_pacientes';

    public static function implementacion(): string
    {
        $valor = (string) config('tenant.tesoreria.implementacion', self::IMPL_MOVIMIENTOS);

        return in_array($valor, [self::IMPL_PACIENTES, self::IMPL_MOVIMIENTOS], true)
            ? $valor
            : self::IMPL_MOVIMIENTOS;
    }

    /** Variante mayoría (`tesoreria_movimientos`): UI sobre filas en `pacientes`. */
    public static function usaMovimientos(): bool
    {
        return self::implementacion() === self::IMPL_MOVIMIENTOS;
    }

    /** Variante labvetciudad (`tesoreria_pacientes`): UI sobre tabla `movimientos`. */
    public static function usaPacientes(): bool
    {
        return self::implementacion() === self::IMPL_PACIENTES;
    }

    /**
     * Grupo Tesorería en el menú y acceso a rutas `tesoreria.*`.
     * Default true. Solo se oculta si el tenant declara `mostrar_modulo => false`.
     */
    public static function mostrarModulo(): bool
    {
        return (bool) config('tenant.tesoreria.mostrar_modulo', true);
    }

    /**
     * Botón «Pago global» en Pacientes y Cuenta Corriente (NeoLab).
     * Default false. Requiere declaración expresa `pago_global => true`
     * y variante `tesoreria_movimientos` (ingresos en `pacientes`).
     */
    public static function pagoGlobalHabilitado(): bool
    {
        return (bool) config('tenant.tesoreria.pago_global', false)
            && self::usaMovimientos();
    }

    /**
     * Columna Pagado editable en Gestión de Pacientes (staff).
     * Default false. Independiente de AFIP: requiere `columna_pagado => true`.
     */
    public static function columnaPagadoHabilitada(): bool
    {
        return (bool) config('tenant.tesoreria.columna_pagado', false);
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
