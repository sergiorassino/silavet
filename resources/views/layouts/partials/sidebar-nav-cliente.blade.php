<a href="{{ route('cliente.home') }}"
   class="vl-sidebar-link {{ request()->routeIs('cliente.home') ? 'is-active' : '' }}"
   title="Inicio (v1.0)">
    <x-vl-sidebar-icon name="inicio" class="h-5 w-5 shrink-0 opacity-80" />
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Inicio</span>
</a>

<a href="{{ route('cliente.pacientes') }}"
   class="vl-sidebar-link {{ request()->routeIs('cliente.pacientes', 'cliente.pacientes.informe') ? 'is-active' : '' }}"
   title="Pacientes (v1.0)">
    <x-vl-sidebar-icon name="pacientes" class="h-5 w-5 shrink-0 opacity-80" />
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Pacientes</span>
</a>

<a href="{{ route('cliente.lista-precios') }}"
   class="vl-sidebar-link {{ request()->routeIs('cliente.lista-precios*') ? 'is-active' : '' }}"
   title="Lista de Precios (v1.0)">
    <x-vl-sidebar-icon name="lista-precios" class="h-5 w-5 shrink-0 opacity-80" />
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Lista de Precios</span>
</a>

<a href="{{ route('cliente.estimacion-costos') }}"
   class="vl-sidebar-link {{ request()->routeIs('cliente.estimacion-costos') ? 'is-active' : '' }}"
   title="Estimación de Costos (v1.0)">
    <x-vl-sidebar-icon name="estimacion-costos" class="h-5 w-5 shrink-0 opacity-80" />
    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Estimación de Costos</span>
</a>
