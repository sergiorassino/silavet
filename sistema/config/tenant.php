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

    /*
    | Mapeo de idRoles legacy → portal de navegación.
    | NeoLab (lb_neolab): 1 = cliente veterinaria, 2 = Pleno (lab), 3 = Administrativo.
    */
    'roles' => [
        'cliente' => [1],
        'administracion' => [3],
    ],

    /*
    | Acceso temporal: todos los roles de personal ven y acceden a todos los módulos.
    | Poner en false cuando se implemente el control fino por roles/permisos.
    */
    'acceso' => [
        'temporal_todos_modulos' => true,
    ],

    /*
    | Gestión de determinaciones (grid admin).
    | - mostrar_columna_perfil: tenant alqu = true; resto = false (default).
    | - derivacion: si_no (Sí/No en destino) | catalogo (select derivaciones).
    |   neolab y laboratoriosiv usan catalogo; resto usa si_no.
    */
    'tipodeterminaciones' => [
        'mostrar_columna_perfil' => false,
        'derivacion' => 'si_no',
    ],

    /*
    | Generación de número de protocolo al alta.
    | Ver docs/10-numero-de-protocolo.md (variantes, config por tenant, cómo extender).
    */
    'protocolos' => [
        'implementacion' => 'fecha_diaria',

        'anual_consecutivo' => [
            'longitud_secuencia' => 5,
        ],

        'fecha_diaria' => [
            'longitud_secuencia' => 3,
        ],

        'dual_corto_largo' => [
            'corto_prefijo' => 'C',
            'corto_inicio' => 101,
            'corto_longitud' => 9,
            'largo_secuencia_len' => 3,
            'tipo_default' => 'L',
        ],
    ],

];
