@props([
    'name',
    'class' => 'h-4 w-4',
])

@php
    $attrs = $attributes->merge([
        'class' => $class,
        'fill' => 'none',
        'stroke' => 'currentColor',
        'viewBox' => '0 0 24 24',
        'aria-hidden' => 'true',
    ]);
@endphp

<svg {{ $attrs }}>
    @switch($name)
        @case('inicio')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            @break

        {{-- Iconos de grupos (no reutilizar en opciones) --}}
        @case('grupo-gestion')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            @break
        @case('grupo-clientes')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            @break
        @case('grupo-tesoreria')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            @break
        @case('grupo-stock')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            @break
        @case('grupo-parametros-generales')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
            @break
        @case('grupo-parametros-determinaciones')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            @break
        @case('grupo-listados-estadisticos')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            @break
        @case('grupo-procedimientos-muestras')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
            @break

        {{-- Iconos de opciones (no reutilizar en grupos) --}}
        @case('pacientes')
            <circle cx="11" cy="4" r="2" stroke-width="1.75"/>
            <circle cx="18" cy="8" r="2" stroke-width="1.75"/>
            <circle cx="20" cy="16" r="2" stroke-width="1.75"/>
            <circle cx="6" cy="16" r="2" stroke-width="1.75"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9 10a5 5 0 0 1 6 0v4a5 5 0 0 1-6 0v-4z"/>
            @break
        @case('derivaciones')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            @break
        @case('determinaciones')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h6m-6 4h6"/>
            @break
        @case('grupos-determinacion')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            @break
        @case('det-por-grupo')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M4 6h16M4 10h16M4 14h10M4 18h10"/>
            @break
        @case('items-informe')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
            @break
        @case('automatizacion')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            @break
        @case('centros-derivacion')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            @break
        @case('parametros-sistema')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
            @break
        @case('gestion-clientes')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            @break
        @case('gestion-usuarios')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            @break
        @case('especies')
            <circle cx="8" cy="7" r="1.75" stroke-width="1.75"/>
            <circle cx="12" cy="5" r="1.75" stroke-width="1.75"/>
            <circle cx="16" cy="7" r="1.75" stroke-width="1.75"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M8.5 14.5c0-2.1 1.6-3.75 3.5-3.75s3.5 1.65 3.5 3.75S13.6 18.5 12 18.5s-3.5-1.9-3.5-4z"/>
            @break
        @case('razas')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M12 4v7m0 0l-3.5 5.5M12 11l3.5 5.5M6.5 20.5h11"/>
            <circle cx="12" cy="4" r="1.75" stroke-width="1.75"/>
            <circle cx="8.5" cy="16.5" r="1.5" stroke-width="1.75"/>
            <circle cx="15.5" cy="16.5" r="1.5" stroke-width="1.75"/>
            @break
        @case('cuenta-corriente')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            @break
        @case('movimientos')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
            @break
        @case('transferencias-intercuenta')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M8 7h3m0 0l-2-2m2 2l-2 2m6 6h-3m0 0l2 2m-2-2l2-2"/>
            <rect x="3" y="4" width="7" height="5" rx="1" stroke-width="1.75"/>
            <rect x="14" y="15" width="7" height="5" rx="1" stroke-width="1.75"/>
            @break
        @case('movimientos-entre-cuentas')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M7 8h4m0 0L9 6m2 2L9 10m8 6h-4m0 0l2 2m-2-2l2-2"/>
            <rect x="3.5" y="5" width="6.5" height="4.5" rx="1" stroke-width="1.75"/>
            <rect x="14" y="14.5" width="6.5" height="4.5" rx="1" stroke-width="1.75"/>
            @break
        @case('saldos-por-dia')
            {{-- Calendario + saldo (distinto a cuenta-corriente / movimientos) --}}
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9 15.5h6"/>
            @break
        @case('gestion-conceptos')
            {{-- Etiquetas / catálogo de conceptos (Tesorería / tesoreria_movimientos) --}}
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M6 6h.008v.008H6V6z"/>
            @break
        @case('gestion-proveedores')
            {{-- Edificio / proveedor (Tesorería / tesoreria_movimientos) --}}
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M3.75 21h16.5M4.5 3h15l.75 18H3.75L4.5 3z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9 21v-6h6v6M8.25 8.25h.008v.008H8.25V8.25zm0 3.75h.008v.008H8.25V12zm3.75-3.75h.008v.008H12V8.25zm0 3.75h.008v.008H12V12zm3.75-3.75h.008v.008H15.75V8.25zm0 3.75h.008v.008H15.75V12z"/>
            @break
        @case('cuentas-contables')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
            @break
        @case('cuentas-detalle')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            @break
        @case('estimacion-costos')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9 7h6m-6 4h6m-6 4h3m-7 5h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M16 15.5l1.25 1.25L20 14"/>
            @break
        @case('estadistico-pacientes')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M3 4.5h18M3 9.75h18M3 15h10.5M3 20.25h7.5"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M16.5 14.25v6m0 0l-2.25-2.25M16.5 20.25l2.25-2.25"/>
            @break
        @case('historial-determinaciones')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M12 8v4l2.5 1.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M3.5 7.5h3v-3M20.5 16.5h-3v3"/>
            @break
        @case('cantidad-determinaciones-comparac')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M4.5 19.5h15M7.5 16.5V9M12 16.5V6M16.5 16.5v-4.5"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M6 9.75l3-3 3 2.25 4.5-4.5"/>
            @break
        @case('gestion-procedimientos')
            {{-- Documento de instrucciones (distinto a clipboard de determinaciones) --}}
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            @break
        @case('muestras-por-determinacion')
            {{-- Enlace / asociación procedimiento ↔ determinaciones --}}
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
            @break
        @case('lista-precios')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M12 6v12m-3-9h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9 17h6"/>
            @break

        @default
            @php throw new InvalidArgumentException("Icono de sidebar desconocido: {$name}"); @endphp
    @endswitch
</svg>
