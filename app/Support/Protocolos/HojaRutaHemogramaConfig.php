<?php

namespace App\Support\Protocolos;

/**
 * Lee config('tenant.hoja_ruta_hemograma'): flag + mapa de idItems del laboratorio.
 *
 * IDs por defecto = catálogo NeoLab / ScriptCase de la planilla de hemograma.
 */
final class HojaRutaHemogramaConfig
{
    public const ENCABEZADO_AMARILLO = 'amarillo';

    public const ENCABEZADO_AZUL = 'azul';

    /**
     * Orden y títulos de la grilla (ScriptCase).
     *
     * @var list<array{clave: string, titulo: string, encabezado: string}>
     */
    public const COLUMNAS = [
        ['clave' => 'wbc', 'titulo' => 'WBC', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'lym', 'titulo' => 'LYM', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'mon', 'titulo' => 'MON', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'neb', 'titulo' => 'NEB', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'neu', 'titulo' => 'NEU', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'eos', 'titulo' => 'EOS', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'rbc', 'titulo' => 'RBC', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'vcm', 'titulo' => 'VCM', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'hct', 'titulo' => 'HCT', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'hgb', 'titulo' => 'HGB', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'hcm', 'titulo' => 'HCM', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'chcm', 'titulo' => 'CHCM', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'rdw', 'titulo' => 'RDW', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'plt', 'titulo' => 'PLT', 'encabezado' => self::ENCABEZADO_AMARILLO],
        ['clave' => 'r_ipr', 'titulo' => '%R/IPR', 'encabezado' => self::ENCABEZADO_AZUL],
        ['clave' => 'pt', 'titulo' => 'PT', 'encabezado' => self::ENCABEZADO_AZUL],
    ];

    /**
     * Columna derecha de citologías (ScriptCase). Oculta por defecto.
     *
     * @var list<array{clave: string, titulo: string}>
     */
    public const CITOLOGIAS = [
        ['clave' => 'liq_puncion', 'titulo' => 'Líq.Punción'],
        ['clave' => 'cit_oido', 'titulo' => 'Cit.Oído'],
        ['clave' => 'cit_vaginal', 'titulo' => 'Cit.Vaginal'],
        ['clave' => 'cit_piel', 'titulo' => 'Cit.Piel'],
    ];

    public static function activo(): bool
    {
        return (bool) config('tenant.hoja_ruta_hemograma.activo', true);
    }

    /**
     * Si true, se imprime la columna Líq.Punción / Cit.Oído / Cit.Vaginal / Cit.Piel.
     * Default false: el espacio queda en las columnas de determinación.
     */
    public static function mostrarCitologias(): bool
    {
        return (bool) config('tenant.hoja_ruta_hemograma.mostrar_citologias', false);
    }

    public static function tituloCitologia(string $clave): string
    {
        foreach (self::CITOLOGIAS as $col) {
            if ($col['clave'] === $clave) {
                return $col['titulo'];
            }
        }

        return '';
    }

    /**
     * Columnas de la grilla con el idItems del tenant.
     *
     * @return list<array{clave: string, titulo: string, encabezado: string, id: int}>
     */
    public static function columnas(): array
    {
        $raw = config('tenant.hoja_ruta_hemograma.columnas', []);
        if (! is_array($raw)) {
            $raw = [];
        }

        $out = [];
        foreach (self::COLUMNAS as $col) {
            $out[] = [
                'clave' => $col['clave'],
                'titulo' => $col['titulo'],
                'encabezado' => $col['encabezado'],
                'id' => (int) ($raw[$col['clave']] ?? 0),
            ];
        }

        return $out;
    }

    public static function idEspecial(string $clave): int
    {
        $raw = config('tenant.hoja_ruta_hemograma.especiales', []);
        if (! is_array($raw)) {
            return 0;
        }

        return (int) ($raw[$clave] ?? 0);
    }

    /**
     * @param  list<int>  $pedidos
     */
    public static function itemPedido(int $idItem, array $pedidos): bool
    {
        if ($idItem <= 0) {
            return false;
        }

        return in_array($idItem, $pedidos, true);
    }
}
