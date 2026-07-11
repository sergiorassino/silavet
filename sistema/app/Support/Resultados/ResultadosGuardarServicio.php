<?php

namespace App\Support\Resultados;

use App\Models\Paciente;
use App\Models\Renglon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Persiste valores de carga sin transformar ni inventar datos.
 * Solo actualiza renglones del paciente y solo columnas explícitamente enviadas.
 */
class ResultadosGuardarServicio
{
    /** Tipos cuyo `valor` puede guardarse desde el formulario. */
    private const TIPOS_CON_VALOR = [1, 4, 7, 8, 9];

    /** Tipos cuyo `valor2` puede guardarse desde el formulario. */
    private const TIPOS_CON_VALOR2 = [4, 9];

    /**
     * @param  array<string, string|null>  $valores  clave = idRenglones
     * @param  array<string, string|null>  $valores2  clave = idRenglones
     */
    public function guardar(
        Paciente $paciente,
        array $valores,
        array $valores2,
        ?string $estadoPaciente
    ): void {
        $idsSolicitados = collect(array_keys($valores))
            ->merge(array_keys($valores2))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($idsSolicitados === [] && $estadoPaciente === null) {
            return;
        }

        $renglones = collect();
        if ($idsSolicitados !== []) {
            $renglones = Renglon::query()
                ->where('idPacientes', $paciente->idPacientes)
                ->whereIn('idRenglones', $idsSolicitados)
                ->get(['idRenglones', 'tipoItem'])
                ->keyBy('idRenglones');
        }

        $updatesValor = [];
        $updatesValor2 = [];

        foreach ($valores as $idRaw => $valorRaw) {
            $id = (int) $idRaw;
            $renglon = $renglones->get($id);

            if ($renglon === null) {
                throw ValidationException::withMessages([
                    'valores' => "El renglón {$id} no pertenece a este protocolo.",
                ]);
            }

            if (! in_array((int) $renglon->tipoItem, self::TIPOS_CON_VALOR, true)) {
                throw ValidationException::withMessages([
                    'valores' => "El renglón {$id} no admite valor en carga.",
                ]);
            }

            if (! is_string($valorRaw) && $valorRaw !== null) {
                throw ValidationException::withMessages([
                    'valores' => "Valor inválido para el renglón {$id}.",
                ]);
            }

            $updatesValor[$id] = (string) ($valorRaw ?? '');
        }

        foreach ($valores2 as $idRaw => $valorRaw) {
            $id = (int) $idRaw;
            $renglon = $renglones->get($id);

            if ($renglon === null) {
                throw ValidationException::withMessages([
                    'valores2' => "El renglón {$id} no pertenece a este protocolo.",
                ]);
            }

            if (! in_array((int) $renglon->tipoItem, self::TIPOS_CON_VALOR2, true)) {
                throw ValidationException::withMessages([
                    'valores2' => "El renglón {$id} no admite valor2 en carga.",
                ]);
            }

            if (! is_string($valorRaw) && $valorRaw !== null) {
                throw ValidationException::withMessages([
                    'valores2' => "Valor2 inválido para el renglón {$id}.",
                ]);
            }

            $valor2 = (string) ($valorRaw ?? '');
            if (mb_strlen($valor2) > 100) {
                throw ValidationException::withMessages([
                    'valores2' => "Valor2 demasiado largo en el renglón {$id}.",
                ]);
            }

            $updatesValor2[$id] = $valor2;
        }

        if ($estadoPaciente !== null && ! ResultadosEstadosCatalog::esValido($estadoPaciente)) {
            throw ValidationException::withMessages([
                'estadoPaciente' => 'Estado de protocolo no permitido.',
            ]);
        }

        DB::transaction(function () use ($paciente, $updatesValor, $updatesValor2, $estadoPaciente): void {
            $this->actualizarColumnaEnLote($paciente->idPacientes, 'valor', $updatesValor);
            $this->actualizarColumnaEnLote($paciente->idPacientes, 'valor2', $updatesValor2);

            if ($estadoPaciente !== null) {
                Paciente::query()
                    ->whereKey($paciente->idPacientes)
                    ->update(['estado' => $estadoPaciente]);
            }
        });
    }

    /**
     * @param  array<int, string>  $mapa
     */
    private function actualizarColumnaEnLote(int $idPacientes, string $columna, array $mapa): void
    {
        if ($mapa === []) {
            return;
        }

        if (! in_array($columna, ['valor', 'valor2'], true)) {
            throw ValidationException::withMessages([
                $columna => 'Columna no permitida.',
            ]);
        }

        // Chunks para no superar límites de paquetes MySQL en protocolos muy grandes.
        foreach (array_chunk($mapa, 80, true) as $chunk) {
            $ids = array_keys($chunk);
            $cases = [];
            $bindings = [];

            foreach ($chunk as $id => $valor) {
                $cases[] = 'WHEN ? THEN ?';
                $bindings[] = $id;
                $bindings[] = $valor;
            }

            $inPlaceholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE renglones SET {$columna} = CASE idRenglones "
                .implode(' ', $cases)
                ." END WHERE idPacientes = ? AND idRenglones IN ({$inPlaceholders})";

            $bindings[] = $idPacientes;
            foreach ($ids as $id) {
                $bindings[] = $id;
            }

            DB::update($sql, $bindings);
        }
    }
}
