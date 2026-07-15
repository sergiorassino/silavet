<div class="vl-page">
    <div class="vl-hero">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Administración</p>
                <h1 class="font-bold">{{ config('tenant.nombre') }}</h1>
                <p class="mt-1.5 max-w-xl text-sm text-white/80">
                    Facturación, stock y parámetros del laboratorio.
                </p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-dash-tiles">
        <a href="{{ route('protocolos.index') }}" class="vl-dash-access">
            <span class="vl-dash-access-icon">PA</span>
            <span>
                <strong class="block text-neutral-800">Pacientes</strong>
                <span class="text-sm text-neutral-600">Protocolos analíticos</span>
            </span>
        </a>
        <a href="{{ route('abm.clientes.index') }}" class="vl-dash-access">
            <span class="vl-dash-access-icon">CL</span>
            <span>
                <strong class="block text-neutral-800">Clientes</strong>
                <span class="text-sm text-neutral-600">Veterinarias y clínicas</span>
            </span>
        </a>
        <a href="{{ route('admin.determinaciones.index') }}" class="vl-dash-access">
            <span class="vl-dash-access-icon">DT</span>
            <span>
                <strong class="block text-neutral-800">Gestión Determinaciones</strong>
                <span class="text-sm text-neutral-600">Tipos de análisis y listas de precios</span>
            </span>
        </a>
        <a href="{{ route('admin.grupos.index') }}" class="vl-dash-access">
            <span class="vl-dash-access-icon">GR</span>
            <span>
                <strong class="block text-neutral-800">Gestión de Grupos</strong>
                <span class="text-sm text-neutral-600">Agrupación de ítems en informes</span>
            </span>
        </a>
        <a href="{{ route('admin.det-por-grupo.index') }}" class="vl-dash-access">
            <span class="vl-dash-access-icon">DG</span>
            <span>
                <strong class="block text-neutral-800">Det. por Grupo (Inf)</strong>
                <span class="text-sm text-neutral-600">Plantilla de ítems por determinación</span>
            </span>
        </a>
        <a href="{{ route('admin.automatizacion.script') }}" class="vl-dash-access">
            <span class="vl-dash-access-icon">JS</span>
            <span>
                <strong class="block text-neutral-800">Script de Automatización</strong>
                <span class="text-sm text-neutral-600">Editar entorno.formulas</span>
            </span>
        </a>
    </div>
</div>
