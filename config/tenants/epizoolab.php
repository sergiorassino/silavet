<?php

return [
    'nombre' => 'Epizoolab',

    'protocolos' => [
        'implementacion' => 'anual_consecutivo',
        'orden_listado' => [
            'fechhoy' => 'desc',
            'nombreProtocolo' => 'desc',
        ],
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

    'envio_informes' => [
        'destinatario_paciente' => false,
        'forma_whatsapp' => false,
    ],
];
