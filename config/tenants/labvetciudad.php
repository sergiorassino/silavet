<?php

return [
    'nombre' => 'LabVet Ciudad',

    'protocolos' => [
        'implementacion' => 'anual_consecutivo',
    ],

    'tesoreria' => [
        'implementacion' => 'tesoreria_pacientes',
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
];
