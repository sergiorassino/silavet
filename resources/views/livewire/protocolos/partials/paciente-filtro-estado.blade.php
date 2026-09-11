@php
    $opcionesEstado = \App\Support\Resultados\ResultadosEstadosCatalog::opcionesFiltroListado();
    $filtroAgrupado = $filtroEstado ?? '';
@endphp
<div class="vl-pacientes-fecha-filtro flex max-w-full flex-wrap items-center gap-2 text-xs font-semibold text-neutral-600">
    <label for="{{ $idFiltroEstado }}" class="whitespace-nowrap">Estado</label>
    <select wire:model.live="filtroEstado"
            id="{{ $idFiltroEstado }}"
            class="form-select py-1.5 text-xs w-auto"
            aria-label="Filtrar pacientes por estado">
        <option value="">Todos</option>
        @if ($filtroAgrupado === \App\Livewire\Protocolos\PacienteIndex::FILTRO_PENDIENTES)
            <option value="{{ \App\Livewire\Protocolos\PacienteIndex::FILTRO_PENDIENTES }}">Pendientes de resultado</option>
        @endif
        @if ($filtroAgrupado === \App\Livewire\Protocolos\PacienteIndex::FILTRO_LISTOS)
            <option value="{{ \App\Livewire\Protocolos\PacienteIndex::FILTRO_LISTOS }}">Informes listos</option>
        @endif
        @foreach ($opcionesEstado as $opcion)
            <option value="{{ $opcion['slug'] }}">{{ $opcion['etiqueta'] }}</option>
        @endforeach
    </select>
</div>
