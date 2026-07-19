<?php

return [
    'nombre' => 'NeoLab',

    'facturacion_afip' => [
        'habilitado' => true,
        'modo' => 'paciente',
        'simular' => true,
    ],

    'portal_cliente' => [
        'permite_descarga_excel' => true,
    ],

    'roles' => [
        'cliente' => [1],
        'administracion' => [3],
    ],

    'tipodeterminaciones' => [
        'derivacion' => 'catalogo',
    ],

    'protocolos' => [
        'implementacion' => 'fecha_diaria',
    ],

    'autoanalizadores' => [
        'aparatos' => [
            'edan_h30' => [
                'activo' => true,
                'etiqueta' => 'Edan H30',
                'overrides' => [
                    'WBC' => ['multiplicador' => 1000, 'formato' => 'entero_miles'],
                    'RBC' => ['multiplicador' => 1000000, 'formato' => 'entero_miles'],
                    'PLT' => ['multiplicador' => 1000],
                ],
            ],
            'edan_h60' => [
                'activo' => true,
                'etiqueta' => 'Edan H60',
                'overrides' => [
                    'WBC' => ['multiplicador' => 1000, 'formato' => 'entero_miles'],
                    'RBC' => ['multiplicador' => 1000000, 'formato' => 'entero_miles'],
                    'PLT' => ['multiplicador' => 1000],
                ],
            ],
            'wiener_cm160' => [
                'activo' => true,
                'etiqueta' => 'Wiener CM 160',
                'overrides' => [
                    // Entero sin miles (legado NeoLab/LAM).
                    'UREL' => ['formato' => 'entero'],
                    'GOTL' => ['formato' => 'entero'],
                    'GPTL' => ['formato' => 'entero'],
                    'GGTL' => ['formato' => 'entero'],
                    'ALPL' => ['formato' => 'entero'],
                    'LDHL' => ['formato' => 'entero'],
                    'GLUL' => ['formato' => 'entero'],
                    'TGL' => ['formato' => 'entero'],
                    'COLL' => ['formato' => 'entero'],
                    'HDL2' => ['formato' => 'entero'],
                    'LDLAA' => ['formato' => 'entero'],
                    'CKL' => ['formato' => 'entero'],
                    'LIP' => ['formato' => 'entero'],
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
];
