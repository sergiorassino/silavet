<?php

return [
    'nombre' => 'CIVET Franca',

    'protocolos' => [
        'implementacion' => 'dual_corto_largo',
    ],

    'autoanalizadores' => [
        'aparatos' => [
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
            'edan_h30' => [
                'activo' => true,
                'etiqueta' => 'Edan H30',
                'overrides' => [
                    'WBC' => ['multiplicador' => 1000, 'formato' => 'entero_miles'],
                    'RBC' => ['multiplicador' => 1000000, 'formato' => 'entero_miles'],
                    'PLT' => ['multiplicador' => 1000],
                ],
            ],
            'incaa' => [
                'activo' => true,
                'etiqueta' => 'Incaa',
                'overrides' => [],
            ],
            'incam' => [
                'activo' => true,
                'etiqueta' => 'Incam',
                'overrides' => [],
            ],
        ],
    ],
];
