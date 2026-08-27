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
        'mostrar_modulo' => false,
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
