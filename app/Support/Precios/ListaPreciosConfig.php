<?php

namespace App\Support\Precios;

use App\Models\Cliente;
use App\Models\Paciente;
use Illuminate\Support\Facades\Schema;

/**
 * Alcance de las listas de precio (`tipodeterminaciones.precio` / `precio2` / `precio3`).
 *
 * - `cliente` (default): cada cliente veterinario elige lista (`clientes.listaPreciosCliente`).
 *   Alta y columna con default 1 (= `tipodeterminaciones.precio`).
 * - `paciente`: cada protocolo elige lista (`pacientes.listaPreciosPaciente`). laboratoriosiv.
 *
 * La clave legacy `fija_1` se interpreta como `cliente`.
 *
 * Ver docs/12-listas-de-precios-por-tenant.md.
 */
final class ListaPreciosConfig
{
    public const MODO_PACIENTE = 'paciente';

    public const MODO_CLIENTE = 'cliente';

    public const COLUMNA_PACIENTE = 'listaPreciosPaciente';

    public const COLUMNA_CLIENTE = 'listaPreciosCliente';

    public const DEFAULT = 1;

    public const MIN = 1;

    public const MAX = 3;

    public static function implementacion(): string
    {
        $valor = (string) config('tenant.precios.lista', self::MODO_CLIENTE);

        if ($valor === self::MODO_PACIENTE) {
            return self::MODO_PACIENTE;
        }

        return self::MODO_CLIENTE;
    }

    public static function esPorPaciente(): bool
    {
        return self::implementacion() === self::MODO_PACIENTE;
    }

    public static function esPorCliente(): bool
    {
        return self::implementacion() === self::MODO_CLIENTE;
    }

    public static function mostrarSelectorPaciente(): bool
    {
        return self::esPorPaciente();
    }

    public static function mostrarSelectorCliente(): bool
    {
        return self::esPorCliente();
    }

    public static function mostrarColumnaListadoPacientes(): bool
    {
        return self::esPorPaciente();
    }

    public static function mostrarColumnaListadoClientes(): bool
    {
        return self::esPorCliente();
    }

    /**
     * Acepta 1/2/3, "1", "L.1", "L1", etc. Fuera de rango o vacío → lista 1.
     */
    public static function normalizar(mixed $valor): int
    {
        if ($valor === null || $valor === '') {
            return self::DEFAULT;
        }

        if (is_int($valor) || is_float($valor)) {
            $nro = (int) $valor;

            return ($nro >= self::MIN && $nro <= self::MAX) ? $nro : self::DEFAULT;
        }

        $texto = strtoupper(trim((string) $valor));
        if ($texto === '') {
            return self::DEFAULT;
        }

        if (preg_match('/^L\.?\s*([123])$/', $texto, $m) === 1) {
            return (int) $m[1];
        }

        if (preg_match('/^[123]$/', $texto) === 1) {
            return (int) $texto;
        }

        return self::DEFAULT;
    }

    public static function etiqueta(int $nro): string
    {
        return 'L.'.self::normalizar($nro);
    }

    /**
     * @return array<int, string>
     */
    public static function opciones(): array
    {
        return [
            1 => 'Lista 1',
            2 => 'Lista 2',
            3 => 'Lista 3',
        ];
    }

    public static function nroParaPaciente(?Paciente $paciente): int
    {
        if ($paciente === null) {
            return self::DEFAULT;
        }

        return match (self::implementacion()) {
            self::MODO_PACIENTE => self::normalizar($paciente->getAttribute(self::COLUMNA_PACIENTE)),
            default => self::nroParaCliente($paciente->cliente),
        };
    }

    public static function nroParaCliente(?Cliente $cliente): int
    {
        if (! self::esPorCliente() || $cliente === null) {
            return self::DEFAULT;
        }

        return self::normalizar($cliente->getAttribute(self::COLUMNA_CLIENTE));
    }

    public static function etiquetaParaPaciente(?Paciente $paciente): string
    {
        return self::etiqueta(self::nroParaPaciente($paciente));
    }

    public static function etiquetaParaCliente(?Cliente $cliente): string
    {
        return self::etiqueta(self::nroParaCliente($cliente));
    }

    public static function tieneColumnaPaciente(): bool
    {
        return Schema::hasTable('pacientes')
            && Schema::hasColumn('pacientes', self::COLUMNA_PACIENTE);
    }

    public static function tieneColumnaCliente(): bool
    {
        return Schema::hasTable('clientes')
            && Schema::hasColumn('clientes', self::COLUMNA_CLIENTE);
    }

    public static function mensajeColumnaPacienteFaltante(): string
    {
        return 'No se puede guardar la lista de precios: falta la columna pacientes.listaPreciosPaciente en este laboratorio. '
            .'Ejecute la migración (php artisan lb:migrate-legacy --force) o el SQL de database/sql/listas_precios_paciente_cliente.sql.';
    }

    public static function mensajeColumnaClienteFaltante(): string
    {
        return 'No se puede guardar la lista de precios: falta la columna clientes.listaPreciosCliente en este laboratorio. '
            .'Ejecute la migración (php artisan lb:migrate-legacy --force) o el SQL de database/sql/listas_precios_paciente_cliente.sql.';
    }
}
