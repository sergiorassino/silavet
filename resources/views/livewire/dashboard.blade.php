@php
    $hoy = $metricas['hoy'];
    $estados = $hoy['porEstado'];
@endphp

<div class="vl-page">
    <div class="vl-hero vl-dash-hero">
        <div class="vl-hero-inner">
            <div class="min-w-0">
                <p class="vl-eyebrow">{{ config('tenant.nombre') }}</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Panel de laboratorio</h1>
                <p class="mt-2 max-w-xl text-sm text-white/80 sm:text-base">
                    Hola, {{ labCtx()->usuario()?->apenom ?? 'usuario' }}.
                    Resumen operativo del {{ $metricas['fechaFormato'] }}.
                </p>
            </div>
            @if (tienePermiso(\App\Support\PermisosIaCatalog::PROTOCOLOS))
                <a href="{{ route('protocolos.index') }}" class="vl-dash-cta">
                    <span class="vl-dash-cta-kicker">Acceso principal</span>
                    <span class="vl-dash-cta-title">Pacientes</span>
                    <span class="vl-dash-cta-desc">Protocolos del día e Históricos</span>
                </a>
            @endif
        </div>
    </div>

    <div class="vl-dash-grid">
        {{-- Pacientes del día + distribución por estado --}}
        <section class="vl-card vl-dash-metric vl-dash-metric--wide" aria-labelledby="dash-hoy-title">
            <div class="vl-dash-metric-head">
                <div>
                    <p class="vl-dash-metric-label">Hoy</p>
                    <h2 id="dash-hoy-title" class="vl-dash-metric-title">Pacientes del día</h2>
                </div>
            </div>

            <div class="vl-dash-hoy">
                <div class="vl-dash-donut-wrap" role="img" aria-label="Distribución por estado">
                    <div class="vl-dash-donut" style="background: {{ $hoy['conic'] }}">
                        <div class="vl-dash-donut-hole">
                            <span class="vl-dash-donut-num">{{ $hoy['total'] }}</span>
                            <span class="vl-dash-donut-cap">casos</span>
                        </div>
                    </div>
                </div>

                <ul class="vl-dash-estado-list">
                    @foreach ($estados as $estado)
                        <li class="vl-dash-estado-item">
                            <span class="vl-dash-estado-swatch" style="background: {{ $estado['color'] }}"></span>
                            <span class="vl-dash-estado-name">{{ $estado['etiqueta'] }}</span>
                            <span class="vl-dash-estado-bar" aria-hidden="true">
                                <span class="vl-dash-estado-bar-fill" style="width: {{ $estado['porcentaje'] }}%; background: {{ $estado['color'] }}"></span>
                            </span>
                            <span class="vl-dash-estado-stats">
                                <strong>{{ $estado['cantidad'] }}</strong>
                                <span>{{ number_format($estado['porcentaje'], 1, ',', '') }}%</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Derivaciones pendientes --}}
        <section class="vl-card vl-dash-metric" aria-labelledby="dash-deriv-title">
            <div class="vl-dash-metric-head">
                <div>
                    <p class="vl-dash-metric-label">Derivaciones</p>
                    <h2 id="dash-deriv-title" class="vl-dash-metric-title">Pendientes de devolución</h2>
                </div>
            </div>
            <p class="vl-dash-kpi">{{ $metricas['derivacionesPendientes'] }}</p>
            <p class="vl-dash-metric-hint">Derivadas en protocolos que aún no están en Final ni Final/Env.</p>
            @if (tienePermiso(\App\Support\PermisosIaCatalog::PROTOCOLOS))
                <a href="{{ route('derivaciones.index') }}" class="vl-dash-metric-link">Ver derivaciones</a>
            @endif
            <div class="vl-dash-metric-accent vl-dash-metric-accent--amber" aria-hidden="true"></div>
        </section>

        {{-- Flujo: sin Final / sin Final/Env --}}
        <section class="vl-card vl-dash-metric" aria-labelledby="dash-pend-title">
            <div class="vl-dash-metric-head">
                <div>
                    <p class="vl-dash-metric-label">Flujo</p>
                    <h2 id="dash-pend-title" class="vl-dash-metric-title">Pendientes de cierre</h2>
                </div>
            </div>
            <div class="vl-dash-kpi-pair">
                <div class="vl-dash-kpi-item">
                    <p class="vl-dash-kpi">{{ $metricas['sinFinal'] }}</p>
                    <p class="vl-dash-kpi-caption">Sin Final</p>
                    <p class="vl-dash-metric-hint">Aún no alcanzaron el estado Final.</p>
                </div>
                <div class="vl-dash-kpi-item">
                    <p class="vl-dash-kpi">{{ $metricas['sinFinalEnv'] }}</p>
                    <p class="vl-dash-kpi-caption">Sin Final/Env</p>
                    <p class="vl-dash-metric-hint">Aún no alcanzaron el estado Final/Env.</p>
                </div>
            </div>
            @if (tienePermiso(\App\Support\PermisosIaCatalog::PROTOCOLOS))
                <a href="{{ route('protocolos.index') }}" class="vl-dash-metric-link">Ir a pacientes</a>
            @endif
            <div class="vl-dash-metric-accent vl-dash-metric-accent--sky" aria-hidden="true"></div>
        </section>
    </div>
</div>
