<?php

return [
    'nombre' => 'LAM',

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
            'wiener_cm160' => [
                'activo' => true,
                'etiqueta' => 'Wiener CM 160',
                'overrides' => [
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
                    'CRELc' => ['decimales' => 1],
                    'PROT' => ['decimales' => 1],
                    'ALB' => ['decimales' => 1],
                    'CAIII' => ['decimales' => 1],
                    'FOS' => ['decimales' => 1],
                    'BDL' => ['decimales' => 2],
                    'BTL' => ['decimales' => 2],
                ],
            ],
        ],
    ],
];
