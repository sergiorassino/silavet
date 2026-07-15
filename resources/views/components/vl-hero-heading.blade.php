{{-- Encabezado de página: logo institucional + textos del hero --}}
@props([])

<div {{ $attributes->class(['flex min-w-0 flex-1 items-start gap-4 sm:items-center']) }}>
    <x-vl-lab-logo variant="hero" />
    <div class="min-w-0">
        {{ $slot }}
    </div>
</div>
