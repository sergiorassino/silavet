<?php

namespace App\Support\Itemsinforme;

use Illuminate\Support\Facades\DB;

class ItemsinformeCatalog
{
    /** @return array<int, string> */
    public static function modosCarga(): array
    {
        return [
            1 => 'Cargar Valor',
            2 => 'Valor Fijo',
            3 => 'Título',
            4 => 'Opciones a completar',
            5 => 'Línea',
            7 => 'Fórmula',
            8 => 'Texto Largo',
            9 => 'Dos Valores',
            10 => 'Imagen',
        ];
    }

    /** @return list<int> */
    public static function modosCargaValores(): array
    {
        return array_keys(self::modosCarga());
    }

    /** @return array<int, string> */
    public static function formatosValor(): array
    {
        return [
            1 => 'Entero con separador de miles',
            2 => 'Decimal con 1 dígito dec.',
            3 => 'Decimal con 2 dígitos dec.',
            4 => 'Texto',
        ];
    }

    /** @return array<int, string> */
    public static function siNo(): array
    {
        return [
            0 => 'No',
            1 => 'Sí',
        ];
    }

    /** @return array<int, string> */
    public static function formatosValorSelect(): array
    {
        $opciones = [];
        foreach (self::formatosValor() as $valor => $etiqueta) {
            $opciones[$valor] = "{$etiqueta} ({$valor})";
        }

        return $opciones;
    }

    public static function etiquetaFormatoValor(int $valor): string
    {
        $formatos = self::formatosValor();

        if (isset($formatos[$valor])) {
            return $formatos[$valor];
        }

        return match ($valor) {
            0 => $formatos[1],
            1 => $formatos[2],
            2 => $formatos[4],
            default => (string) $valor,
        };
    }

    public static function formatoValorParaEdicion(int $valor): string
    {
        if (isset(self::formatosValor()[$valor])) {
            return (string) $valor;
        }

        return match ($valor) {
            0 => '1',
            1 => '2',
            2 => '4',
            default => (string) $valor,
        };
    }

    public static function etiquetaModoCarga(int $valor): string
    {
        return self::modosCarga()[$valor] ?? (string) $valor;
    }

    public static function columnasVisibles(): int
    {
        return 16;
    }

    /**
     * Campos editables de la grilla (clave fila => metadato).
     *
     * @return array<string, array{label: string, tipo: string, columna: string, max?: int, hint?: string}>
     */
    public static function camposEditables(): array
    {
        return [
            'nombre_item' => [
                'label' => 'Nombre del ítem',
                'tipo' => 'text',
                'columna' => 'nombreItem',
                'max' => 100,
            ],
            'id_grupos' => [
                'label' => 'Grupo',
                'tipo' => 'select_grupo',
                'columna' => 'idGrupos',
            ],
            'tipo_item' => [
                'label' => 'Modo de carga',
                'tipo' => 'select_modo',
                'columna' => 'tipoItem',
            ],
            'textos' => [
                'label' => 'Textos del select',
                'tipo' => 'textarea',
                'columna' => 'textos',
                'max' => 500,
                'hint' => 'Opciones separadas con # (ej.: Opción 1#Opción 2).',
            ],
            'unidad_medida' => [
                'label' => 'Unidad valor 1',
                'tipo' => 'text',
                'columna' => 'unidadMedida',
                'max' => 20,
            ],
            'unidad_medida2' => [
                'label' => 'Unidad valor 2',
                'tipo' => 'text',
                'columna' => 'unidadMedida2',
                'max' => 20,
            ],
            'ref_caninos' => [
                'label' => 'Referencia caninos',
                'tipo' => 'text',
                'columna' => 'refCaninos',
                'max' => 80,
            ],
            'ref_felinos' => [
                'label' => 'Referencia felinos',
                'tipo' => 'text',
                'columna' => 'refFelinos',
                'max' => 80,
            ],
            'ref_equinos' => [
                'label' => 'Referencia equinos',
                'tipo' => 'text',
                'columna' => 'refEquinos',
                'max' => 80,
            ],
            'ref_porcinos' => [
                'label' => 'Referencia porcinos',
                'tipo' => 'text',
                'columna' => 'refPorcinos',
                'max' => 80,
            ],
            'ref_bovinos' => [
                'label' => 'Referencia bovinos',
                'tipo' => 'text',
                'columna' => 'refBovinos',
                'max' => 80,
            ],
            'estilo_num' => [
                'label' => 'Formato del valor',
                'tipo' => 'select_formato',
                'columna' => 'estiloNum',
            ],
            'actualiza' => [
                'label' => 'Dispara automatización',
                'tipo' => 'select_sino',
                'columna' => 'actualiza',
            ],
            'id_analizador' => [
                'label' => 'Código analizador',
                'tipo' => 'text',
                'columna' => 'idAnalizador',
                'max' => 20,
            ],
        ];
    }

    /**
     * Orden de plantilla por ítem según una determinación de referencia (legacy: Hemograma id 12).
     */
    public static function idDeterminacionOrdenReferencia(): int
    {
        return 12;
    }

    public static function subconsultaOrdenPlantilla(?int $idDeterminacionReferencia = null): \Illuminate\Database\Query\Builder
    {
        $idDeterminacion = $idDeterminacionReferencia ?? self::idDeterminacionOrdenReferencia();

        return DB::table('renglonesxdeterminacion as r')
            ->join('tipodeterminaciones as t', 'r.idTipodeterminaciones', '=', 't.idTipodeterminaciones')
            ->where('r.idTipodeterminaciones', $idDeterminacion)
            ->select('r.idItemsinforme', 'r.orden as orden_plantilla');
    }
}
