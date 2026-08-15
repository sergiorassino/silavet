<?php

return [
    'nombre' => 'Laboratorio SIV',

    'protocolos' => [
        'estados_flujo' => 3,
        'implementacion' => 'anual_consecutivo',
    ],

    'tipodeterminaciones' => [
        'derivacion' => 'catalogo',
    ],

    'autoanalizadores' => [
        'aparatos' => [
            'biosystem_a15' => [
                'activo' => true,
                'etiqueta' => 'Biosystem A15',
                'overrides' => [],
            ],
        ],
    ],
];
