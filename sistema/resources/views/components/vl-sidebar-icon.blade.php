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
        @case('parametros-sistema')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
            @break
        @case('cuenta-corriente')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            @break

        @default
            @php throw new InvalidArgumentException("Icono de sidebar desconocido: {$name}"); @endphp
    @endswitch
</svg>
