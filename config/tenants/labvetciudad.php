<?php

return [
    'nombre' => 'LabVet Ciudad',

    'protocolos' => [
        'estados_flujo' => 3,
        'implementacion' => 'anual_consecutivo',
    ],

    'tesoreria' => [
        'implementacion' => 'tesoreria_pacientes',
    ],

    'tipodeterminaciones' => [
        'derivacion' => 'catalogo',
    ],

    'facturacion_afip' => [
        'habilitado' => true,
        'modo' => 'movimiento_caja',
        'simular' => true,
    ],

    'autoanalizadores' => [
        'aparatos' => [
            'mindray_bc20' => [
                'activo' => true,
                'etiqueta' => 'Mindray BC-20',
                'overrides' => [
                    'WBC' => ['multiplicador' => 1000, 'formato' => 'entero_miles'],
                    'RBC' => ['multiplicador' => 1000000, 'formato' => 'entero_miles'],
                    'PLT' => ['multiplicador' => 1000],
                    'HGB' => ['formato' => 'entero'],
                    'HCT' => ['formato' => 'entero'],
                ],
            ],
            'incaa' => [
                'activo' => true,
                'etiqueta' => 'Incaa',
                'overrides' => [],
            ],
            'metrolab_cm250' => [
                'activo' => true,
                'etiqueta' => 'Metrolab CM 250',
                'overrides' => [
                    // Entero con separador de miles (legado).
                    'GOTL' => ['formato' => 'entero_miles'],
                    'GPTL' => ['formato' => 'entero_miles'],
                    'GGTL' => ['formato' => 'entero_miles'],
                    'ALPL' => ['formato' => 'entero_miles'],
                    // Entero sin miles.
                    'UREL' => ['formato' => 'entero'],
                    'LDHL' => ['formato' => 'entero'],
                    'GLUL' => ['formato' => 'entero'],
                    'TGL' => ['formato' => 'entero'],
                    'COLL' => ['formato' => 'entero'],
                    'HDL2' => ['formato' => 'entero'],
                    'LDLAA' => ['formato' => 'entero'],
                    'CKL' => ['formato' => 'entero'],
                    'LIP' => ['formato' => 'entero'],
                    'AMIL' => ['formato' => 'entero'],
                    // Un decimal.
                    'CRELc' => ['decimales' => 1],
                    'PROT' => ['decimales' => 1],
                    'ALB' => ['decimales' => 1],
                    'CAIII' => ['decimales' => 1],
                    'FOS' => ['decimales' => 1],
                    // Dos decimales.
                    'BDL' => ['decimales' => 2],
                    'BTL' => ['decimales' => 2],
                ],
            ],
        ],
    ],

    /*
    | Serie Roja / Serie Blanca — ids de itemsinforme de este laboratorio
    | (misma numeración que el ScriptCase / Excel de hemograma automático).
    */
    'hemograma_auto' => [
        'activo' => true,
        'items' => [
            'hto' => 3,
            'eritrocitos' => 1,
            'hb' => 29,
            'vcm' => 2,
            'chcm' => 5,
            'plaquetas' => 18,
            'plaquetas_conteo_manual' => 239,
            'leucocitos' => 6,
            'neutrofilos' => 10,
            'bandas' => 9,
            'linfocitos' => 7,
            'eosinofilos' => 11,
            'basofilos' => 12,
            'monocitos' => 8,
            'serie_roja' => 209,
            'serie_blanca' => 210,
        ],
    ],
];
