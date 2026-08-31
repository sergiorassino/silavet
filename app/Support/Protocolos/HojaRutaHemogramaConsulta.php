<?php

namespace App\Support\Protocolos;

use App\Models\Paciente;
use App\Models\Renglon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Protocolos del día para la hoja de ruta de hemograma.
 *
 * Mismo criterio que el ScriptCase: fechhoy del día, tipoRegistro = protocolo,
 * renglones.mostrar = 1 como “pedida”.
 */
final class HojaRutaHemogramaConsulta
{
    /** Tope de caracteres para el nombre de la veterinaria en la planilla. */
    public const CLIENTE_MAX_CHARS = 28;

    /**
     * @return array{
     *     fecha: string,
     *     fecha_texto: string,
     *     filas: list<array{
     *         nombreProtocolo: string,
     *         nombre: string,
     *         especie: string,
     *         raza: string,
     *         sexo: string,
     *         edad: string,
     *         cliente: string,
     *         detPedidas: list<int>
     *     }>
     * }
     */
    public static function paraFecha(string $fecha): array
    {
        $fecha = self::normalizarFecha($fecha);

        if (! Schema::hasTable('pacientes')) {
            return [
                'fecha' => $fecha,
                'fecha_texto' => self::fechaTexto($fecha),
                'filas' => [],
            ];
        }

        $pacientes = Paciente::query()
            ->with(['especie', 'raza', 'cliente'])
            ->whereDate('pacientes.fechhoy', $fecha)
            ->where('pacientes.tipoRegistro', Paciente::TIPO_PROTOCOLO)
            ->orderBy('pacientes.nombreProtocolo')
            ->get([
                'idPacientes',
                'nombreProtocolo',
                'nombre',
                'sexo',
                'edad',
                'idEspecies',
                'idRazas',
                'idClientes',
            ]);

        $ids = $pacientes->pluck('idPacientes')->map(fn ($id) => (int) $id)->all();
        $pedidosPorPaciente = self::pedidosPorPaciente($ids);

        $filas = [];
        foreach ($pacientes as $paciente) {
            $id = (int) $paciente->idPacientes;
            $filas[] = [
                'nombreProtocolo' => self::texto($paciente->nombreProtocolo),
                'nombre' => self::texto($paciente->nombre),
                'especie' => self::texto($paciente->especie?->nombre),
                'raza' => self::texto($paciente->raza?->nombre),
                'sexo' => self::texto($paciente->sexo),
                'edad' => self::texto($paciente->edad),
                'cliente' => self::abreviar(self::texto($paciente->cliente?->nombre)),
                'detPedidas' => $pedidosPorPaciente[$id] ?? [],
            ];
        }

        return [
            'fecha' => $fecha,
            'fecha_texto' => self::fechaTexto($fecha),
            'filas' => $filas,
        ];
    }

    public static function normalizarFecha(string $fecha): string
    {
        $fecha = trim($fecha);
        if ($fecha === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return now()->toDateString();
        }

        return $fecha;
    }

    public static function fechaTexto(string $fecha): string
    {
        return Carbon::createFromFormat('Y-m-d', $fecha)?->format('d/m/Y') ?? $fecha;
    }

    public static function abreviar(string $texto, int $max = self::CLIENTE_MAX_CHARS): string
    {
        $texto = trim($texto);
        $max = max(1, $max);
        if ($texto === '') {
            return '';
        }

        $len = function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
        if ($len <= $max) {
            return $texto;
        }

        $corte = $max - 1;
        $corto = function_exists('mb_substr')
            ? mb_substr($texto, 0, $corte, 'UTF-8')
            : substr($texto, 0, $corte);

        return rtrim($corto).'…';
    }

    /**
     * Líneas de la columna de identificación (sin vacías).
     *
     * @param  array<string, mixed>  $fila
     * @return list<string>
     */
    public static function lineasIdentificacion(array $fila): array
    {
        $protocolo = self::texto($fila['nombreProtocolo'] ?? '');
        $nombre = self::texto($fila['nombre'] ?? '');
        $espRaza = self::unir(' · ', [
            self::texto($fila['especie'] ?? ''),
            self::texto($fila['raza'] ?? ''),
        ]);
        $sexoEdad = self::unir(' · ', [
            self::texto($fila['sexo'] ?? ''),
            self::texto($fila['edad'] ?? ''),
        ]);
        $cliente = self::texto($fila['cliente'] ?? '');

        return array_values(array_filter(
            [$protocolo, $nombre, $espRaza, $sexoEdad, $cliente],
            static fn (string $linea): bool => $linea !== '',
        ));
    }

    /**
     * @param  list<int>  $idsPacientes
     * @return array<int, list<int>>
     */
    private static function pedidosPorPaciente(array $idsPacientes): array
    {
        if ($idsPacientes === [] || ! Schema::hasTable('renglones')) {
            return [];
        }

        $rows = Renglon::query()
            ->whereIn('idPacientes', $idsPacientes)
            ->where('mostrar', 1)
            ->get(['idPacientes', 'idItems']);

        $out = [];
        foreach ($rows as $row) {
            $idPaciente = (int) $row->idPacientes;
            $idItem = (int) $row->idItems;
            if ($idItem <= 0) {
                continue;
            }
            $out[$idPaciente][$idItem] = $idItem;
        }

        foreach ($out as $idPaciente => $ids) {
            $out[$idPaciente] = array_values($ids);
        }

        return $out;
    }

    private static function texto(mixed $valor): string
    {
        return trim((string) ($valor ?? ''));
    }

    /**
     * @param  list<string>  $partes
     */
    private static function unir(string $separador, array $partes): string
    {
        $partes = array_values(array_filter($partes, static fn (string $p): bool => $p !== ''));

        return implode($separador, $partes);
    }
}
