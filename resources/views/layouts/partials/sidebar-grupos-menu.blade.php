{{-- Grupos del menú lateral — catálogo de iconos: <x-vl-sidebar-icon> · docs/08-menus-de-navegacion.md --}}

<x-vl-sidebar-grupo group-key="gestion" label="Gestión" title="Gestión v1.0" :first="true">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-gestion" /></x-slot:icon>

    @if (tienePermiso(\App\Support\PermisosIaCatalog::PROTOCOLOS))
        <a href="{{ route('protocolos.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('protocolos.index', 'protocolos.create', 'protocolos.edit', 'protocolos.determinaciones') ? 'is-active' : '' }}"
           title="Gestión de Pacientes (v1.0)">
            <x-vl-sidebar-icon name="pacientes" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Gestión de Pacientes</span>
        </a>
        <a href="{{ route('derivaciones.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('derivaciones.*') ? 'is-active' : '' }}"
           title="Gestión de Derivaciones (v1.0)">
            <x-vl-sidebar-icon name="derivaciones" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Gestión de Derivaciones</span>
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

    @if (tienePermiso(\App\Support\PermisosIaCatalog::FACTURACION))
        <a href="{{ route('tesoreria.movimientos.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('tesoreria.movimientos.*') ? 'is-active' : '' }}"
           title="Movimientos (v1.0)">
            <x-vl-sidebar-icon name="movimientos" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Movimientos</span>
        </a>
        @if (\App\Support\Tesoreria\TesoreriaConfig::usaPacientes())
            <a href="{{ route('tesoreria.movimientos-entre-cuentas.index') }}"
               class="vl-sidebar-link {{ request()->routeIs('tesoreria.movimientos-entre-cuentas.*') ? 'is-active' : '' }}"
               title="Movimientos entre Cuentas (v1.0)">
                <x-vl-sidebar-icon name="movimientos-entre-cuentas" class="h-4 w-4 shrink-0 opacity-80" />
                <span class="truncate">Movimientos entre Cuentas</span>
            </a>
            <a href="{{ route('tesoreria.saldos-por-dia.index') }}"
               class="vl-sidebar-link {{ request()->routeIs('tesoreria.saldos-por-dia.*') ? 'is-active' : '' }}"
               title="Saldos por Día (v1.0)">
                <x-vl-sidebar-icon name="saldos-por-dia" class="h-4 w-4 shrink-0 opacity-80" />
                <span class="truncate">Saldos por Día</span>
            </a>
            <a href="{{ route('tesoreria.conceptos.index') }}"
               class="vl-sidebar-link {{ request()->routeIs('tesoreria.conceptos.*') ? 'is-active' : '' }}"
               title="Gestión de Conceptos (v1.0)">
                <x-vl-sidebar-icon name="gestion-conceptos" class="h-4 w-4 shrink-0 opacity-80" />
                <span class="truncate">Gestión de Conceptos</span>
            </a>
            <a href="{{ route('tesoreria.proveedores.index') }}"
               class="vl-sidebar-link {{ request()->routeIs('tesoreria.proveedores.*') ? 'is-active' : '' }}"
               title="Gestión de Proveedores (v1.0)">
                <x-vl-sidebar-icon name="gestion-proveedores" class="h-4 w-4 shrink-0 opacity-80" />
                <span class="truncate">Gestión de Proveedores</span>
            </a>
        @else
            <a href="{{ route('tesoreria.transferencias.index') }}"
               class="vl-sidebar-link {{ request()->routeIs('tesoreria.transferencias.*') ? 'is-active' : '' }}"
               title="Transferencias Intercuenta (v1.0)">
                <x-vl-sidebar-icon name="transferencias-intercuenta" class="h-4 w-4 shrink-0 opacity-80" />
                <span class="truncate">Transferencias Intercuenta</span>
            </a>
            <a href="{{ route('tesoreria.cuentas.index') }}"
               class="vl-sidebar-link {{ request()->routeIs('tesoreria.cuentas.*') ? 'is-active' : '' }}"
               title="Gestión de Cuentas Contables (v1.0)">
                <x-vl-sidebar-icon name="cuentas-contables" class="h-4 w-4 shrink-0 opacity-80" />
                <span class="truncate">Gestión de Cuentas Contables</span>
            </a>
            <a href="{{ route('tesoreria.cuentas-detalle.index') }}"
               class="vl-sidebar-link {{ request()->routeIs('tesoreria.cuentas-detalle.*') ? 'is-active' : '' }}"
               title="Gestión de Cuentas Detalle (v1.0)">
                <x-vl-sidebar-icon name="cuentas-detalle" class="h-4 w-4 shrink-0 opacity-80" />
                <span class="truncate">Gestión de Cuentas Detalle</span>
            </a>
        @endif
    @endif
</x-vl-sidebar-grupo>

<x-vl-sidebar-grupo group-key="gestionStock" label="Gestión de Stock" title="Gestión de Stock v1.0">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-stock" /></x-slot:icon>
</x-vl-sidebar-grupo>

<x-vl-sidebar-grupo group-key="parametrosGenerales" label="Parámetros Generales" title="Parámetros Generales v1.0">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-parametros-generales" /></x-slot:icon>

    @if (tienePermiso(\App\Support\PermisosIaCatalog::CLIENTES))
        <a href="{{ route('abm.clientes.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('abm.clientes.*') ? 'is-active' : '' }}"
           title="Gestión de Clientes (v1.0)">
            <x-vl-sidebar-icon name="gestion-clientes" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Gestión de Clientes</span>
        </a>
    @endif

    @if (tienePermiso(\App\Support\PermisosIaCatalog::ESPECIES))
        <a href="{{ route('abm.especies.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('abm.especies.*') ? 'is-active' : '' }}"
           title="Gestión de Especies (v1.0)">
            <x-vl-sidebar-icon name="especies" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Gestión de Especies</span>
        </a>
        <a href="{{ route('abm.razas.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('abm.razas.*') ? 'is-active' : '' }}"
           title="Gestión de Razas (v1.0)">
            <x-vl-sidebar-icon name="razas" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Gestión de Razas</span>
        </a>
    @endif

    @if (tienePermiso(\App\Support\PermisosIaCatalog::USUARIOS))
        <a href="{{ route('abm.usuarios.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('abm.usuarios.*') ? 'is-active' : '' }}"
           title="Gestión de Usuarios (v1.0)">
            <x-vl-sidebar-icon name="gestion-usuarios" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Gestión de Usuarios</span>
        </a>
    @endif

    @if (tienePermiso(\App\Support\PermisosIaCatalog::PARAMETROS))
        <a href="{{ route('abm.derivaciones.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('abm.derivaciones.*') ? 'is-active' : '' }}"
           title="Gestión de Centros de Derivación (v1.0)">
            <x-vl-sidebar-icon name="centros-derivacion" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Gestión de Centros de Derivación</span>
        </a>
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

    @if (tienePermiso(\App\Support\PermisosIaCatalog::LISTADOS_ESTADISTICOS))
        <a href="{{ route('listados.estimacion-costos') }}"
           class="vl-sidebar-link {{ request()->routeIs('listados.estimacion-costos') ? 'is-active' : '' }}"
           title="Estimación de Costos (v1.0)">
            <x-vl-sidebar-icon name="estimacion-costos" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Estimación de Costos</span>
        </a>
        <a href="{{ route('listados.estadistico-pacientes') }}"
           class="vl-sidebar-link {{ request()->routeIs('listados.estadistico-pacientes*') ? 'is-active' : '' }}"
           title="Listado Estadístico de Pacientes (v1.0)">
            <x-vl-sidebar-icon name="estadistico-pacientes" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Listado Estadístico de Pacientes</span>
        </a>
        <a href="{{ route('listados.historial-determinaciones') }}"
           class="vl-sidebar-link {{ request()->routeIs('listados.historial-determinaciones*') ? 'is-active' : '' }}"
           title="Historial de Determinaciones (v1.0)">
            <x-vl-sidebar-icon name="historial-determinaciones" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Historial de Determinaciones</span>
        </a>
        <a href="{{ route('listados.cantidad-determinaciones-comparac') }}"
           class="vl-sidebar-link {{ request()->routeIs('listados.cantidad-determinaciones-comparac*') ? 'is-active' : '' }}"
           title="Cantidad Determinaciones (comparac.) (v1.0)">
            <x-vl-sidebar-icon name="cantidad-determinaciones-comparac" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Cantidad Determinaciones (comparac.)</span>
        </a>
        <a href="{{ route('listados.excel-pacientes') }}"
           class="vl-sidebar-link {{ request()->routeIs('listados.excel-pacientes*') ? 'is-active' : '' }}"
           title="Excel de Pacientes (v1.0)">
            <x-vl-sidebar-icon name="excel-pacientes" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Excel de Pacientes</span>
        </a>
    @endif
</x-vl-sidebar-grupo>

<x-vl-sidebar-grupo group-key="procedimientosTomaMuestras" label="Procedimientos Toma de Muestras" title="Procedimientos Toma de Muestras v1.0">
    <x-slot:icon><x-vl-sidebar-icon name="grupo-procedimientos-muestras" /></x-slot:icon>
    @if (tienePermiso(\App\Support\PermisosIaCatalog::PARAMETROS))
        <a href="{{ route('abm.requerimientos.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('abm.requerimientos.*') ? 'is-active' : '' }}"
           title="Gestión de Procedimientos (v1.0)">
            <x-vl-sidebar-icon name="gestion-procedimientos" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Gestión de Procedimientos</span>
        </a>
        <a href="{{ route('abm.muestras-por-determinacion.index') }}"
           class="vl-sidebar-link {{ request()->routeIs('abm.muestras-por-determinacion.*') ? 'is-active' : '' }}"
           title="Muestras por Determinación (v1.0)">
            <x-vl-sidebar-icon name="muestras-por-determinacion" class="h-4 w-4 shrink-0 opacity-80" />
            <span class="truncate">Muestras por Determinación</span>
        </a>
    @endif
</x-vl-sidebar-grupo>
