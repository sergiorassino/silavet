{{-- Controles sidebar (solo desktop): modo automático/manual y contraer/expandir en manual --}}
<div class="relative z-[1] hidden shrink-0 border-t vl-sidebar-sep md:flex"
     :class="sidebarCollapsed ? 'flex-col items-center gap-1.5 px-1.5 py-2' : 'items-center justify-between gap-2 px-3 py-2'">
    <button type="button"
            @click="toggleSidebarControlMode()"
            class="vl-sidebar-iconbtn rounded-lg p-2 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--vl-light-blue)]"
            :title="sidebarControlMode === 'dynamic'
                ? 'Modo automático: el menú se expande al pasar el mouse. Clic para modo manual.'
                : 'Modo manual: usted controla el ancho del menú. Clic para modo automático.'">
        <svg x-show="sidebarControlMode === 'dynamic'" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
        </svg>
        <svg x-show="sidebarControlMode === 'manual'" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 100 3m0-3a1.5 1.5 0 100 3m0-3v6m6-9v6m0 0a1.5 1.5 0 103 0m-3 0a1.5 1.5 0 103 0m0 0v6"/>
        </svg>
        <span class="sr-only" x-text="sidebarControlMode === 'dynamic' ? 'Cambiar a modo manual' : 'Cambiar a modo automático'"></span>
    </button>

    <button type="button"
            x-show="sidebarControlMode === 'manual'"
            x-cloak
            @click="toggleSidebarManual()"
            class="vl-sidebar-iconbtn rounded-lg p-2 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--vl-light-blue)]"
            :title="sidebarCollapsed ? 'Expandir menú lateral' : 'Contraer menú lateral'">
        <svg x-show="!sidebarCollapsed" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
        </svg>
        <svg x-show="sidebarCollapsed" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
        </svg>
        <span class="sr-only" x-text="sidebarCollapsed ? 'Expandir menú' : 'Contraer menú'"></span>
    </button>

    <p x-show="!sidebarCollapsed && sidebarControlMode === 'manual'" x-cloak
       class="min-w-0 flex-1 truncate text-right text-[10px] font-semibold uppercase tracking-wide text-white/55">
        Menú manual
    </p>
    <p x-show="!sidebarCollapsed && sidebarControlMode === 'dynamic'" x-cloak
       class="min-w-0 flex-1 truncate text-right text-[10px] font-semibold uppercase tracking-wide text-white/55">
        Menú automático
    </p>
</div>
