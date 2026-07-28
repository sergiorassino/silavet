{{-- Encabezado de página: logo institucional + textos del hero --}}
@props([])

{{-- Móvil: logo arriba / texto abajo (logos wide no aplastan el título). Desde sm: en fila. --}}
<div {{ $attributes->class(['flex min-w-0 flex-1 flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-4']) }}>
    <x-vl-lab-logo variant="hero" />
    <div class="min-w-0 w-full sm:w-auto sm:flex-1">
        {{ $slot }}
    </div>
</div>
