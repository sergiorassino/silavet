<?php

namespace App\Support\Informes;

use App\Models\Entorno;
use App\Models\Imagenxrenglon;
use App\Models\Paciente;
use App\Models\Renglon;
use App\Support\Entorno\EntornoArchivos;
use App\Support\Entorno\LabInstitucional;
use App\Support\Protocolos\PacienteAdjuntoStorage;
use App\Support\Resultados\RenglonImagenesStorage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Arma en pocas consultas los datos del informe PDF (sin N+1 por grupo).
 */
final class InformePacienteConsulta
{
    /**
     * @return array{
     *     paciente: array<string, mixed>,
     *     header: array{nombre: string, direccion: string, telefono: string, logo_file: ?string},
     *     color_rgb: array{0: int, 1: int, 2: int},
     *     footer: array<string, mixed>,
     *     grupos: list<array{idGrupos: int, nombreGrupo: string, renglones: list<array<string, mixed>>}>,
     *     adjunto_ruta: ?string,
     * }|null
     */
    public static function armar(Paciente $paciente): ?array
    {
        $paciente->loadMissing(['cliente', 'especie', 'raza', 'medicoSolicitante']);

        $idEspecies = (int) ($paciente->idEspecies ?? 0);

        $entorno = Schema::hasTable('entorno')
            ? Entorno::query()->find(1)
            : null;

        $grupos = self::gruposConRenglones($paciente, $idEspecies);

        return [
            'paciente' => [
                'idPacientes' => (int) $paciente->idPacientes,
                'protocolo' => trim((string) ($paciente->nombreProtocolo ?? '')),
                'fecha' => $paciente->fechhoyFormateada(),
                'nombre' => trim((string) ($paciente->nombre ?? '')),
                'propietario' => trim((string) ($paciente->propietario ?? '')),
                'sexo' => trim((string) ($paciente->sexo ?? '')),
                'edad' => trim((string) ($paciente->edad ?? '')),
                'especie' => trim((string) ($paciente->especie?->nombre ?? '')),
                'raza' => trim((string) ($paciente->raza?->nombre ?? '')),
                'cliente' => trim((string) ($paciente->cliente?->nombre ?? '')),
                'observaciones' => trim((string) ($paciente->observaciones ?? '')),
                'idEspecies' => $idEspecies,
                'rotulo_ref' => self::rotuloReferencia($idEspecies),
            ],
            'header' => LabInstitucional::datosParaPdf(),
            'color_rgb' => self::colorRgb($entorno),
            'footer' => self::footerDesdeEntorno($entorno),
            'grupos' => $grupos,
            'adjunto_ruta' => self::rutaAdjuntoPdf($paciente),
        ];
    }

    /**
     * @return list<array{idGrupos: int, nombreGrupo: string, renglones: list<array<string, mixed>>}>
     */
    private static function gruposConRenglones(Paciente $paciente, int $idEspecies): array
    {
        if (! Schema::hasTable('renglones') || ! Schema::hasTable('itemsinforme')) {
            return [];
        }

        $query = Renglon::query()
            ->from('renglones as r')
            ->join('itemsinforme as i', 'r.idItems', '=', 'i.idItems')
            ->join('grupos as g', 'r.idGrupos', '=', 'g.idGrupos')
            ->leftJoin('tipodeterminaciones as t', 'r.idTipodeterminacion', '=', 't.idTipodeterminaciones')
            ->where('r.idPacientes', $paciente->idPacientes)
            ->where('r.mostrar', 1)
            ->select([
                'r.idRenglones',
                'r.idGrupos',
                'r.orden',
                'r.tipoItem',
                'r.valor',
                'r.valor2',
                'i.nombreItem',
                'i.unidadMedida',
                'i.unidadMedida2',
                'i.estiloNum',
                'i.refCaninos',
                'i.refFelinos',
                'i.refEquinos',
                'i.refBovinos',
                'i.refPorcinos',
                'i.refOvinos',
                'i.refComun',
                'g.nombreGrupo',
                'g.orden as ordenGrupo',
            ])
            ->orderBy('g.orden')
            ->orderBy('g.idGrupos')
            ->orderBy('t.orden');

        if (Schema::hasColumn('renglones', 'duplic')) {
            $query->orderBy('r.duplic');
        }

        /** @var Collection<int, object> $filas */
        $filas = $query
            ->orderBy('r.orden')
            ->orderBy('r.idRenglones')
            ->get();

        $idsRenglones = $filas->pluck('idRenglones')->map(fn ($id) => (int) $id)->all();
        $imagenesPorRenglon = self::imagenesPorRenglon($idsRenglones);

        $grupos = [];
        $indicePorGrupo = [];

        foreach ($filas as $fila) {
            $idGrupos = (int) $fila->idGrupos;
            if (! isset($indicePorGrupo[$idGrupos])) {
                $indicePorGrupo[$idGrupos] = count($grupos);
                $grupos[] = [
                    'idGrupos' => $idGrupos,
                    'nombreGrupo' => (string) $fila->nombreGrupo,
                    'renglones' => [],
                ];
            }

            $idRenglon = (int) $fila->idRenglones;
            $estiloNum = (int) ($fila->estiloNum ?? 0);
            $valor = self::textoInforme(self::formatearValor((string) ($fila->valor ?? ''), $estiloNum));
            $valor2 = self::textoInforme((string) ($fila->valor2 ?? ''));

            $grupos[$indicePorGrupo[$idGrupos]]['renglones'][] = [
                'idRenglones' => $idRenglon,
                'nombreItem' => self::textoInforme((string) ($fila->nombreItem ?? '')),
                'valor' => $valor,
                'valor2' => $valor2,
                'tipoItem' => (int) $fila->tipoItem,
                'unidadMedida' => (string) ($fila->unidadMedida ?? ''),
                'unidadMedida2' => (string) ($fila->unidadMedida2 ?? ''),
                'referencia' => self::textoInforme(self::referenciaEspecie($fila, $idEspecies)),
                'imagenes' => $imagenesPorRenglon[$idRenglon] ?? [],
            ];
        }

        return array_values(array_filter(
            $grupos,
            static fn (array $g): bool => $g['renglones'] !== []
        ));
    }

    /**
     * @param  list<int>  $idsRenglones
     * @return array<int, list<array{ruta: string, observacion: string}>>
     */
    private static function imagenesPorRenglon(array $idsRenglones): array
    {
        if ($idsRenglones === [] || ! Schema::hasTable('imagenesxrenglon')) {
            return [];
        }

        $mapa = [];
        $registros = Imagenxrenglon::query()
            ->whereIn('idRenglones', $idsRenglones)
            ->orderByDesc('id')
            ->get(['idRenglones', 'nombreImagen', 'observacion']);

        foreach ($registros as $img) {
            $nombre = RenglonImagenesStorage::nombreSeguro((string) ($img->nombreImagen ?? ''));
            if ($nombre === null) {
                continue;
            }
            $ruta = RenglonImagenesStorage::rutaAbsoluta($nombre);
            if (! is_file($ruta)) {
                continue;
            }

            $idR = (int) $img->idRenglones;
            $mapa[$idR] ??= [];
            $mapa[$idR][] = [
                'ruta' => $ruta,
                'observacion' => trim((string) ($img->observacion ?? '')),
            ];
        }

        return $mapa;
    }

    private static function formatearValor(string $valor, int $estiloNum): string
    {
        if ($estiloNum === 2 || $estiloNum === 3) {
            return (string) preg_replace('/(\d+)\.(\d+)/', '$1,$2', $valor);
        }

        return $valor;
    }

    /**
     * Normaliza texto de ítems/referencias: <br> → salto de línea, sin tags HTML.
     */
    private static function textoInforme(string $texto): string
    {
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $texto) ?? $texto;
        $texto = strip_tags($texto);
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace("/[ \t]+\n/", "\n", $texto) ?? $texto;
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto) ?? $texto;

        return trim($texto);
    }

    private static function referenciaEspecie(object $fila, int $idEspecies): string
    {
        $campo = match ($idEspecies) {
            1 => 'refCaninos',
            5 => 'refFelinos',
            6 => 'refEquinos',
            7 => 'refBovinos',
            11 => 'refOvinos',
            12 => 'refPorcinos',
            default => 'refComun',
        };

        return trim((string) ($fila->{$campo} ?? ''));
    }

    public static function rotuloReferencia(int $idEspecies): string
    {
        return match ($idEspecies) {
            1 => 'Caninos',
            5 => 'Felinos',
            6 => 'Equinos',
            7 => 'Bovino',
            11 => 'Ovinos',
            12 => 'Porcinos',
            default => '',
        };
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function colorRgb(?Entorno $entorno): array
    {
        $raw = trim((string) ($entorno?->colorInforme ?? ''));

        if (preg_match('/^#([0-9A-Fa-f]{6})$/', $raw, $m) === 1) {
            $hex = $m[1];

            return [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
            ];
        }

        // Legacy ScriptCase: "r,g,b"
        if (preg_match('/^(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})$/', $raw, $m) === 1) {
            return [
                min(255, (int) $m[1]),
                min(255, (int) $m[2]),
                min(255, (int) $m[3]),
            ];
        }

        $hex = ltrim((string) (LabInstitucional::datos()['color_informe'] ?? '#0EA5E9'), '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * @return array{
     *     texto1footerIzq: string,
     *     texto2footerIzq: string,
     *     texto1footerCentro: string,
     *     texto2footerCentro: string,
     *     texto1footerDer: string,
     *     texto2footerDer: string,
     *     firmaIzq: ?string,
     *     firmaCentro: ?string,
     *     firmaDer: ?string,
     * }
     */
    private static function footerDesdeEntorno(?Entorno $entorno): array
    {
        $firma = static function (?string $ruta): ?string {
            $normalizada = EntornoArchivos::normalizarRutaLegacy(trim((string) $ruta) ?: null);

            return EntornoArchivos::rutaAbsoluta($normalizada);
        };

        return [
            'texto1footerIzq' => trim((string) ($entorno?->texto1footerIzq ?? '')),
            'texto2footerIzq' => trim((string) ($entorno?->texto2footerIzq ?? '')),
            'texto1footerCentro' => trim((string) ($entorno?->texto1footerCentro ?? '')),
            'texto2footerCentro' => trim((string) ($entorno?->texto2footerCentro ?? '')),
            'texto1footerDer' => trim((string) ($entorno?->texto1footerDer ?? '')),
            'texto2footerDer' => trim((string) ($entorno?->texto2footerDer ?? '')),
            'firmaIzq' => $firma($entorno?->firmaIzq ?? null),
            'firmaCentro' => $firma($entorno?->firmaCentro ?? null),
            'firmaDer' => $firma($entorno?->firmaDer ?? null),
        ];
    }

    private static function rutaAdjuntoPdf(Paciente $paciente): ?string
    {
        $nombre = PacienteAdjuntoStorage::nombreSeguro((string) ($paciente->adjunto ?? ''));
        if ($nombre === null) {
            return null;
        }

        $ruta = PacienteAdjuntoStorage::rutaAbsoluta($nombre);

        return is_file($ruta) ? $ruta : null;
    }
}
