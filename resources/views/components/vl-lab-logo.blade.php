@props([
    'size' => 'md',
    'variant' => 'default',
    'monogramClass' => '',
])

@php
    $datos = \App\Support\Entorno\LabInstitucional::datos();

    $imgClass = match ($size) {
        'sm' => 'max-h-8 max-w-[6rem]',
        'md' => 'max-h-10 max-w-[8rem]',
        'lg' => 'max-h-16 max-w-[12rem]',
        'xl' => 'max-h-14 max-w-[10rem]',
        default => 'max-h-10 max-w-[8rem]',
    };

    $monogramSizes = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-16 w-16 text-2xl',
        'xl' => 'h-14 w-14 text-lg',
        default => 'h-10 w-10 text-sm',
    };

    $logoShape = 'square';
    $logoFile = $datos['logo_file'] ?? null;
    if (is_string($logoFile) && is_file($logoFile)) {
        $sizeInfo = @getimagesize($logoFile);
        if (is_array($sizeInfo) && ($sizeInfo[1] ?? 0) > 0) {
            $ratio = $sizeInfo[0] / $sizeInfo[1];
            $logoShape = $ratio >= 1.35 ? 'wide' : ($ratio <= 0.75 ? 'tall' : 'square');
        }
    }
@endphp

@if ($variant === 'login')
    <div {{ $attributes->class(['flex w-full justify-center']) }}>
        @if ($datos['logo_url'])
            <div class="vl-auth-logo-frame"
                 x-data="vlAuthLogoFrame({ shape: @js($logoShape) })"
                 x-bind:class="frameClass"
                 x-bind:style="frameStyle">
                <img src="{{ $datos['logo_url'] }}"
                     alt="{{ $datos['nombre'] }}"
                     class="vl-auth-logo"
                     @load="onLoad($event)">
            </div>
        @else
            <span class="vl-auth-logo-fallback vl-auth-logo-fallback--panel">
                {{ $datos['iniciales'] }}
            </span>
        @endif
    </div>
@elseif ($datos['logo_url'])
    <img src="{{ $datos['logo_url'] }}"
         alt="{{ $datos['nombre'] }}"
         {{ $attributes->class([$imgClass, 'shrink-0 rounded-xl object-contain']) }}>
@else
    <span {{ $attributes->class([
        'flex shrink-0 items-center justify-center rounded-xl font-bold',
        $monogramSizes,
        $monogramClass !== '' ? $monogramClass : 'bg-primary-100 text-primary-700 ring-2 ring-primary-200',
    ]) }}>
        {{ $datos['iniciales'] }}
    </span>
@endif
