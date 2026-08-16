<?php

return [
    'nombre' => 'CIVET Franca',

    'tipodeterminaciones' => [
        'derivacion' => 'catalogo',
    ],

    'protocolos' => [
        'estados_flujo' => 3,
        'implementacion' => 'dual_corto_largo',
    ],

    'autoanalizadores' => [
        'aparatos' => [
            'edan_h30' => [
                'activo' => true,
                'etiqueta' => 'Edan H 30',
                'overrides' => [
                    'WBC' => ['multiplicador' => 1000, 'formato' => 'entero_miles'],
                    'RBC' => ['multiplicador' => 1000000, 'formato' => 'entero_miles'],
                    'PLT' => ['multiplicador' => 1000],
                ],
            ],
            'geo_mc' => [
                'activo' => true,
                'etiqueta' => 'Geo MC',
                'overrides' => [
                    // Legacy Scriptcase: WBC/RBC con miles; PLT solo ×1000.
                    'WBC' => ['multiplicador' => 1000, 'formato' => 'entero_miles'],
                    'RBC' => ['multiplicador' => 1000000, 'formato' => 'entero_miles'],
                    'PLT' => ['multiplicador' => 1000],
                ],
            ],
            'incaa' => [
                'activo' => true,
                'etiqueta' => 'Incca',
                'overrides' => [],
            ],
            'incam' => [
                'activo' => true,
                'etiqueta' => 'Incca v2',
                'overrides' => [],
            ],
        ],
    ],
];
