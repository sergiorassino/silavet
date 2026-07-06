<a href="{{ $homeRoute ?? route('dashboard') }}"
   class="vl-sidebar-link {{ request()->routeIs('dashboard', 'admin.dashboard') ? 'is-active' : '' }}"
   title="Inicio">
    <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Inicio</span>
</a>
<a href="{{ route('protocolos.index') }}"
   class="vl-sidebar-link {{ request()->routeIs('protocolos.*') ? 'is-active' : '' }}"
   title="Pacientes (v1.0)">
    <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
    </svg>
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Pacientes</span>
</a>
<a href="{{ route('abm.clientes.index') }}"
   class="vl-sidebar-link {{ request()->routeIs('abm.clientes.*') ? 'is-active' : '' }}"
   title="ABM Clientes (v1.0)">
    <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Clientes</span>
</a>
<a href="{{ route('admin.determinaciones.index') }}"
   class="vl-sidebar-link {{ request()->routeIs('admin.determinaciones.*') ? 'is-active' : '' }}"
   title="Gestión Determinaciones (Administ) (v1.0)">
    <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h6m-6 4h6"/>
    </svg>
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Gestión Determinaciones</span>
</a>
<a href="{{ route('admin.grupos.index') }}"
   class="vl-sidebar-link {{ request()->routeIs('admin.grupos.*') ? 'is-active' : '' }}"
   title="Gestión de Grupos (v1.0)">
    <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
    </svg>
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Gestión de Grupos</span>
</a>
<a href="{{ route('admin.det-por-grupo.index') }}"
   class="vl-sidebar-link {{ request()->routeIs('admin.det-por-grupo.*') ? 'is-active' : '' }}"
   title="Det. por Grupo (Inf) (v1.0)">
    <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
              d="M4 6h16M4 10h16M4 14h10M4 18h10"/>
    </svg>
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Det. por Grupo (Inf)</span>
</a>
