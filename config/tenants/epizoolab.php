<?php

return [
    'nombre' => 'Epizoolab',

    'protocolos' => [
        'implementacion' => 'anual_consecutivo',
    ],

    'tesoreria' => [
        'columna_pagado' => true,
        'pago_global' => true,
        'mostrar_modulo' => false,
    ],

    'portal_cliente' => [
        'mostrar_lista_precios' => false,
        'mostrar_estimacion_costos' => false,
        'mostrar_saldo_cuenta_corriente' => false,
        'mostrar_descuentos_obtenidos' => false,
    ],

    'tipodeterminaciones' => [
        'derivacion' => 'catalogo',
    ],
];
