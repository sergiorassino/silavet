{{-- Grupos del menú lateral — catálogo de iconos: <x-vl-sidebar-icon> · docs/08-menus-de-navegacion.md --}}

<x-vl-sidebar-grupo group-key="gestion" label="Gestión" title="Gestión v1.0" :first="true">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-gestion" /></x-slot:icon>

    @if (tienePermiso(\App\Support\PermisosIaCatalog::PROTOCOLOS))
        <a href="{{ route('protocolos.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('protocolos.*') ? 'is-active' : '' }}"
           title="Gestión de Pacientes (v1.0)">
            <x-vl-sidebar-icon name="pacientes" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Gestión de Pacientes</span>
        </a>
    @endif
</x-vl-sidebar-grupo>

<x-vl-sidebar-grupo group-key="clientes" label="Clientes" title="Clientes v1.0">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-clientes" /></x-slot:icon>

    @if (tienePermiso(\App\Support\PermisosIaCatalog::FACTURACION))
        <a href="{{ route('clientes.cuenta-corriente.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('clientes.cuenta-corriente*') ? 'is-active' : '' }}"
           title="Cuenta Corriente (v1.0)">
            <x-vl-sidebar-icon name="cuenta-corriente" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Cuenta Corriente</span>
        </a>
    @endif
</x-vl-sidebar-grupo>

<x-vl-sidebar-grupo group-key="tesoreria" label="Tesorería" title="Tesorería v1.0">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-tesoreria" /></x-slot:icon>
</x-vl-sidebar-grupo>

<x-vl-sidebar-grupo group-key="gestionStock" label="Gestión de Stock" title="Gestión de Stock v1.0">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-stock" /></x-slot:icon>
</x-vl-sidebar-grupo>

<x-vl-sidebar-grupo group-key="parametrosGenerales" label="Parámetros Generales" title="Parámetros Generales v1.0">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-parametros-generales" /></x-slot:icon>

    @if (tienePermiso(\App\Support\PermisosIaCatalog::PARAMETROS))
        <a href="{{ route('admin.parametros-sistema.edit') }}"
           class="vl-sidebar-link {{ request()->routeIs('admin.parametros-sistema.*') ? 'is-active' : '' }}"
           title="Parámetros del Sistema (v1.0)">
            <x-vl-sidebar-icon name="parametros-sistema" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Parámetros del Sistema</span>
        </a>
    @endif
</x-vl-sidebar-grupo>

<x-vl-sidebar-grupo group-key="parametrosDeterminaciones" label="Parámetros Determinaciones" title="Parámetros Determinaciones v1.0">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-parametros-determinaciones" /></x-slot:icon>

    @if (tienePermiso(\App\Support\PermisosIaCatalog::DETERMINACIONES))
        <a href="{{ route('admin.determinaciones.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('admin.determinaciones.*') ? 'is-active' : '' }}"
           title="Gestión de Determinaciones (Administ) (v1.0)">
            <x-vl-sidebar-icon name="determinaciones" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Gestión de Determinaciones (Administ)</span>
        </a>
    @endif

    @if (tienePermiso(\App\Support\PermisosIaCatalog::PARAMETROS))
        <a href="{{ route('admin.grupos.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('admin.grupos.*') ? 'is-active' : '' }}"
           title="Gestión de Grupos (v1.0)">
            <x-vl-sidebar-icon name="grupos-determinacion" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Gestión de Grupos</span>
        </a>
        <a href="{{ route('admin.det-por-grupo.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('admin.det-por-grupo.*') ? 'is-active' : '' }}"
           title="Det. por Grupo (Inf) (v1.0)">
            <x-vl-sidebar-icon name="det-por-grupo" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Det. por Grupo (Inf)</span>
        </a>
        <a href="{{ route('admin.items-informe.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('admin.items-informe.*') ? 'is-active' : '' }}"
           title="Parametrización de Items (v1.0)">
            <x-vl-sidebar-icon name="items-informe" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Parametrización de Items</span>
        </a>
        <a href="{{ route('admin.automatizacion.script') }}"
           class="vl-sidebar-link {{ request()->routeIs('admin.automatizacion.*') ? 'is-active' : '' }}"
           title="Script de Automatización (v1.0)">
            <x-vl-sidebar-icon name="automatizacion" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Script de Automatización</span>
        </a>
    @endif
</x-vl-sidebar-grupo>

<x-vl-sidebar-grupo group-key="listadosEstadisticos" label="Listados Estadísticos" title="Listados Estadísticos v1.0">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-listados-estadisticos" /></x-slot:icon>
</x-vl-sidebar-grupo>

<x-vl-sidebar-grupo group-key="procedimientosTomaMuestras" label="Procedimientos Toma de Muestras" title="Procedimientos Toma de Muestras v1.0">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-procedimientos-muestras" /></x-slot:icon>
</x-vl-sidebar-grupo>
