<a href="{{ route('tutor.pacientes') }}"
   class="vl-sidebar-link {{ request()->routeIs('tutor.pacientes', 'tutor.pacientes.informe') ? 'is-active' : '' }}"
   title="Pacientes (v1.0)">
    <x-vl-sidebar-icon name="pacientes" class="h-5 w-5 shrink-0 opacity-80" />
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Pacientes</span>
</a>
