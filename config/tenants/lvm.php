<?php

return [
    'nombre' => 'LVM',

    'facturacion_afip' => [
        'habilitado' => true,
        'modo' => 'paciente',
        'simular' => true,
    ],

    'autoanalizadores' => [
        'aparatos' => [
            'incaa' => [
                'activo' => true,
                'etiqueta' => 'Incaa',
                'overrides' => [],
            ],
        ],
    ],
];
