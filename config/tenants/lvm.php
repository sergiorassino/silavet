<?php

return [
    'nombre' => 'LVM',

    'facturacion_afip' => [
        'habilitado' => true,
        'modo' => 'paciente',
        'simular' => true,
    ],

    'tesoreria' => [
        'columna_pagado' => true,
        'pago_global' => true,
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

    'protocolos' => [
        'implementacion' => 'consecutivo_simple',
    ],
];
