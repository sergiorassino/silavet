@props([
    'groupKey',
    'label',
    'title' => null,
    'first' => false,
])

@if (! $first)
    <div class="mt-4"></div>
@endif

<button type="button"
        class="vl-sidebar-groupbtn w-full flex items-center gap-2 rounded-md px-2.5 py-2 text-[11px] font-semibold uppercase tracking-wide transition-colors"
        :class="(groups.{{ $groupKey }} && !sidebarCollapsed) ? 'is-open' : ''"
        @click="toggleGroup('{{ $groupKey }}')"
        title="{{ $title ?? $label }}">
    @isset($icon)
        <span class="vl-sidebar-group-icon flex h-5 w-5 shrink-0 items-center justify-center opacity-90">{!! $icon !!}</span>
    @endisset
    <span x-show="!sidebarCollapsed" x-cloak class="vl-sidebar-group-label min-w-0 flex-1 truncate text-left">{{ $label }}</span>
    <svg x-show="!sidebarCollapsed" x-cloak class="vl-sidebar-group-chevron h-4 w-4 shrink-0 transition-transform"
         :class="groups.{{ $groupKey }} ? 'rotate-180' : ''"
         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
    </svg>
</button>

<div class="vl-sidebar-group-items mt-1 space-y-0.5"
     x-show="groups.{{ $groupKey }} && !sidebarCollapsed"
     x-collapse
     x-cloak>
    {{ $slot }}
</div>
