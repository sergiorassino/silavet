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
    $logoDense = false;
    $logoFile = $datos['logo_file'] ?? null;
    if (is_string($logoFile) && is_file($logoFile)) {
        $sizeInfo = @getimagesize($logoFile);
        if (is_array($sizeInfo) && ($sizeInfo[1] ?? 0) > 0) {
            $ratio = $sizeInfo[0] / $sizeInfo[1];
            // 1.2: logos horizontales con poco padding (p. ej. ALQU) no llegan a 1.35.
            $logoShape = $ratio >= 1.2 ? 'wide' : ($ratio <= 0.75 ? 'tall' : 'square');
            $logoDense = $logoShape === 'wide' && $ratio < 2.8;
        }
    }
@endphp

@if ($variant === 'login')
    <div {{ $attributes->class(['flex w-full justify-center']) }}>
        @if ($datos['logo_url'])
            <div class="vl-auth-logo-frame{{ $logoShape === 'wide' ? ' vl-auth-logo-frame--wide' : '' }}{{ $logoDense ? ' vl-auth-logo-frame--dense' : '' }}"
                 x-data="vlAuthLogoFrame({ shape: @js($logoShape), variant: 'login' })"
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
@elseif ($variant === 'sidebar')
    @if ($datos['logo_url'])
        <span {{ $attributes->class([
                    'vl-sidebar-brand__mark',
                    'vl-sidebar-brand__mark--'.$logoShape,
                ]) }}
              data-logo-shape="{{ $logoShape }}"
              x-data="vlAuthLogoFrame({ shape: @js($logoShape), variant: 'sidebar' })"
              x-bind:class="frameClass"
              x-bind:style="frameStyle">
            <img src="{{ $datos['logo_url'] }}"
                 alt="{{ $datos['nombre'] }}"
                 class="vl-sidebar-brand__logo"
                 decoding="async"
                 @load="onLoad($event)">
        </span>
    @else
        <span {{ $attributes->class(['vl-sidebar-brand__mark', 'vl-sidebar-brand__mark--square']) }}>
            <span class="vl-sidebar-brand__monogram {{ $monogramClass }}">
                {{ $datos['iniciales'] }}
            </span>
        </span>
    @endif
@elseif ($variant === 'hero')
    @if ($datos['logo_url'])
        <span {{ $attributes->class(['vl-hero-logo', 'vl-hero-logo--'.$logoShape]) }}
              data-logo-shape="{{ $logoShape }}">
            <img src="{{ $datos['logo_url'] }}"
                 alt="{{ $datos['nombre'] }}"
                 class="vl-hero-logo__img"
                 decoding="async">
        </span>
    @else
        <span {{ $attributes->class(['vl-hero-logo', 'vl-hero-logo--square']) }}>
            <span class="vl-hero-logo__monogram {{ $monogramClass }}">
                {{ $datos['iniciales'] }}
            </span>
        </span>
    @endif
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
