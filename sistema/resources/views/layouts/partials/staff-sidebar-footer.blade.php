@php
    $usuario = labCtx()->usuario();
    $inicial = strtoupper(substr((string) ($usuario?->apenom ?? 'U'), 0, 1));
@endphp

<div class="relative z-[1] border-t vl-sidebar-sep px-4 py-3"
     :class="sidebarCollapsed ? 'px-1.5 py-2.5' : ''">
    <div class="flex items-center gap-3"
         :class="sidebarCollapsed ? 'flex-col gap-2' : ''">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
             style="background: var(--vl-primary);">
            <span class="text-[13px] font-bold text-white">{{ $inicial }}</span>
        </div>
        <div class="min-w-0 flex-1" x-show="!sidebarCollapsed" x-cloak>
            <p class="truncate text-[13px] font-medium text-white/90">
                {{ $usuario?->apenom ?? '' }}
            </p>
            @if (labCtx()->rolNombre())
                <p class="truncate text-[11px] text-white/60">{{ labCtx()->rolNombre() }}</p>
            @endif
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    title="Cerrar sesión"
                    class="text-white/85 transition-colors hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </form>
    </div>
</div>
