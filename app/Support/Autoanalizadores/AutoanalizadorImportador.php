<?php

namespace App\Support\Autoanalizadores;

use App\Models\Paciente;
use App\Models\Renglon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Orquesta: archivo → driver → perfil lab → UPDATE renglones por idAnalizador
 * (y idAnalizador2 / idAnalizador3 si existen en la tabla, legado SIV).
 */
final class AutoanalizadorImportador
{
    public function __construct(
        private readonly AutoanalizadorCarpeta $carpeta = new AutoanalizadorCarpeta,
        private readonly AutoanalizadorValorFormatter $formatter = new AutoanalizadorValorFormatter,
    ) {}

    /**
     * @return array{actualizados: int, valores: array<string, string>}
     */
    public function importar(Paciente $paciente, string $claveAparato, string $nombreArchivo): array
    {
        $aparato = AutoanalizadorConfig::aparato($claveAparato);
        if ($aparato === null) {
            throw new RuntimeException('Aparato no configurado o inactivo para este laboratorio.');
        }

        $protocolo = trim((string) ($paciente->nombreProtocolo ?? ''));
        if ($protocolo === '') {
            throw new RuntimeException('El protocolo no tiene número para cruzar con el archivo.');
        }

        $ruta = $this->carpeta->rutaSegura($nombreArchivo);
        $driver = AutoanalizadorDriverRegistry::resolver($claveAparato);
        $crudos = $driver->buscarPorProtocolo($ruta, $protocolo);

        if ($crudos === null) {
            throw new RuntimeException('No encuentro datos del paciente en este archivo.');
        }

        if ($crudos === []) {
            throw new RuntimeException('Aparentemente el archivo seleccionado no es del equipo indicado.');
        }

        $valores = $this->formatter->formatear($crudos, $aparato['overrides']);
        if ($valores === []) {
            throw new RuntimeException('No hay valores para importar.');
        }

        $columnas = $this->columnasAnalizador();
        $codigos = array_keys($valores);

        $renglones = Renglon::query()
            ->where('idPacientes', $paciente->idPacientes)
            ->where(function ($q) use ($codigos, $columnas): void {
                foreach ($columnas as $col) {
                    $q->orWhere(function ($q2) use ($col, $codigos): void {
                        $q2->where($col, '!=', '')
                            ->whereIn($col, $codigos);
                    });
                }
            })
            ->get(array_values(array_unique(array_merge(['idRenglones'], $columnas))));

        if ($renglones->isEmpty()) {
            throw new RuntimeException('Este protocolo no tiene ítems con idAnalizador coincidente.');
        }

        $actualizados = 0;

        DB::transaction(function () use ($paciente, $renglones, $valores, $columnas, &$actualizados): void {
            foreach ($renglones as $renglon) {
                $codigo = $this->codigoCoincidente($renglon, $columnas, $valores);
                if ($codigo === null) {
                    continue;
                }

                Renglon::query()
                    ->where('idPacientes', $paciente->idPacientes)
                    ->where('idRenglones', $renglon->idRenglones)
                    ->update(['valor' => $valores[$codigo]]);

                $actualizados++;
            }
        });

        return [
            'actualizados' => $actualizados,
            'valores' => $valores,
        ];
    }

    /**
     * @return list<string>
     */
    private function columnasAnalizador(): array
    {
        $columnas = ['idAnalizador'];
        foreach (['idAnalizador2', 'idAnalizador3'] as $extra) {
            if (Schema::hasColumn('renglones', $extra)) {
                $columnas[] = $extra;
            }
        }

        return $columnas;
    }

    /**
     * @param  list<string>  $columnas
     * @param  array<string, string>  $valores
     */
    private function codigoCoincidente(Renglon $renglon, array $columnas, array $valores): ?string
    {
        foreach ($columnas as $col) {
            $codigo = (string) ($renglon->{$col} ?? '');
            if ($codigo !== '' && array_key_exists($codigo, $valores)) {
                return $codigo;
            }
        }

        return null;
    }
}
