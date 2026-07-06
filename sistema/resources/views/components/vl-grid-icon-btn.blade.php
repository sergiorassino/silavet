@props([
    'title',
    'variant' => 'neutral',
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'text-primary-700 hover:bg-primary-50',
        'danger' => 'text-red-600 hover:bg-red-50',
        'info' => 'text-sky-600 hover:bg-sky-50',
        'warning' => 'text-amber-600 hover:bg-amber-50',
        'success' => 'text-emerald-600 hover:bg-emerald-50',
        default => 'text-neutral-600 hover:bg-neutral-100',
    };
@endphp

<button type="button"
        title="{{ $title }}"
        aria-label="{{ $title }}"
        {{ $attributes->merge(['class' => "vl-grid-icon-btn {$variantClasses}"]) }}>
    {{ $slot }}
</button>
