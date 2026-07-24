<?php

return [
    'nombre' => 'ALQU',

    'facturacion_afip' => [
        'habilitado' => true,
        'modo' => 'movimiento',
        'simular' => true,
    ],

    'protocolos' => [
        'estados_flujo' => 3,
    ],

    'tipodeterminaciones' => [
        'mostrar_columna_perfil' => true,
	'derivacion' => 'catalogo',
    ],

    'precios' => [
        'descuento' => 'perfiles_volumen_mes_anterior',
    ],

    'autoanalizadores' => [
        'aparatos' => [
            'exigo_h400' => [
                'activo' => true,
                'etiqueta' => 'Exigo H400',
                'overrides' => [
                    // Legacy Scriptcase: ×1000 / ×1e6 y separador de miles.
                    'WBC' => ['multiplicador' => 1000, 'formato' => 'entero_miles'],
                    'RBC' => ['multiplicador' => 1000000, 'formato' => 'entero_miles'],
                    'PLT' => ['multiplicador' => 1000, 'formato' => 'entero_miles'],
                ],
            ],
            'mindray_bs120' => [
                'activo' => true,
                'etiqueta' => 'Mindray BS-120',
                'overrides' => [
                    // Redondeo por ItemID (legado Scriptcase ALQU).
                    '2' => ['formato' => 'entero'],
                    '5' => ['formato' => 'entero'],
                    '28' => ['formato' => 'entero'],
                    '29' => ['formato' => 'entero'],
                    '31' => ['formato' => 'entero'],
                    '61' => ['formato' => 'entero'],
                    '69' => ['formato' => 'entero'],
                    '70' => ['formato' => 'entero'],
                    '72' => ['formato' => 'entero'],
                    '74' => ['formato' => 'entero'],
                    '80' => ['formato' => 'entero'],
                    '82' => ['formato' => 'entero'],
                    '91' => ['formato' => 'entero'],
                    '92' => ['formato' => 'entero'],
                    '93' => ['formato' => 'entero'],
                    '4' => ['decimales' => 2],
                    '10' => ['decimales' => 2],
                    '20' => ['decimales' => 2],
                    '22' => ['decimales' => 2],
                    '35' => ['decimales' => 2],
                    '90' => ['decimales' => 2],
                    '42' => ['decimales' => 1],
                    '64' => ['decimales' => 1],
                    '65' => ['decimales' => 1],
                ],
            ],
        ],
    ],
];
