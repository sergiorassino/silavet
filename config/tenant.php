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
    | Descuento al cargar determinaciones.
    | - cliente_porcentaje: % fijo en clientes.descuento, aplica a todas las determinaciones.
    | - perfiles_volumen_mes_anterior: según perfiles pedidos el mes anterior (tipodeterminaciones.perfil),
    |   descuento escalonado solo en perfiles del mes actual; no usa clientes.descuento.
    */
    'precios' => [
        'descuento' => 'cliente_porcentaje',
    ],

    /*
    | Generación de número de protocolo al alta.
    | Ver docs/10-numero-de-protocolo.md (variantes, config por tenant, cómo extender).
    */
    'protocolos' => [
        /*
        | Estados del flujo del protocolo: 3 (En Proc., Parcial, Final) o 4 (+ Final/Env).
        | neolab y la mayoría usan 4; alqu usa 3.
        */
        'estados_flujo' => 4,

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

    /*
    | Tesorería — variantes por laboratorio.
    | Ver docs/11-tesoreria-por-tenant.md.
    | - tesoreria_pacientes: ingresos/egresos en tabla pacientes (mayoría de labs).
    | - tesoreria_movimientos: caja sobre tabla movimientos (labvetciudad).
    */
    'tesoreria' => [
        'implementacion' => 'tesoreria_pacientes',

        'movimientos' => [
            'concepto_ingresos_diarios' => 'Ingresos Diarios',
            'concepto_cadeteria' => 'Cadetería',
            'dias_protocolos' => 7,
        ],
    ],

    /*
    | Facturación AFIP — individual (sin masiva). Emisor y certs en `usuarios`.
    | - modo paciente: icono en protocolos (tipoRegistro 1→paciente, 2→cliente).
    | - modo movimiento: icono en movimientos, solo ingresos (→cliente).
    */
    'facturacion_afip' => [
        'habilitado' => false,
        /** @var 'paciente'|'movimiento' */
        'modo' => 'paciente',
        'produccion' => true,
        /** Si true, no llama a AFIP (CAE simulado). Default: true hasta homologación/producción real. */
        'simular' => true,
        /** En local, simula salvo que el tenant declare `simular => false` explícito. */
        'simular_local' => true,
        'cbte_tipo' => 11,
        'nota_credito_tipo' => 12,
        'comanda_tipo' => 888,
        'concepto' => 2,
        'doc_tipo_dni' => 96,
        'doc_tipo_cuit' => 80,
        /** DocTipo 99 — consumidor final sin identificar (DocNro 0). */
        'doc_tipo_consumidor_final' => 99,
        /** Desde este importe AFIP exige DNI/CUIT/CUIL/CDI del comprador (normativa 2025). */
        'importe_minimo_identificacion_cf' => 10_000_000,
        'condicion_iva_receptor_id' => 5,
    ],

    /*
    | Autoanalizadores — importación de CSV desde storage/app/AUTOANALIZADORES.
    | Cada tenant declara solo los aparatos activos y sus overrides de formato.
    | Drivers en App\Support\Autoanalizadores\Drivers (registry por clave).
    */
    'autoanalizadores' => [
        'carpeta' => '', // vacío = storage/app/AUTOANALIZADORES
        'dias_retencion' => 7,
        'aparatos' => [
            // Ejemplo:
            // 'mindray_bc20' => [
            //     'activo' => true,
            //     'etiqueta' => 'Mindray BC-20',
            //     'overrides' => [
            //         'WBC' => ['multiplicador' => 1000, 'formato' => 'entero_miles'],
            //     ],
            // ],
        ],
    ],

];
