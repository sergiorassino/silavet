<?php

return [

    'slug' => env('TENANT_SLUG', 'default'),

    'nombre' => 'Laboratorio Veterinario',

    'institucional' => [
        'logo_fallback' => 'img/logo-main.png',
    ],

    'login' => [
        'titulo_staff' => 'Portal del personal',
        'titulo_cliente' => 'Portal de clientes',
    ],

    'portal_cliente' => [
        'permite_descarga_excel' => true,
    ],

];
