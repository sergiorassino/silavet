<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Listados estadísticos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Listado estadístico de pacientes</h1>
                <p class="mt-2 text-sm text-white/80">
                    Protocolos filtrables por cliente, paciente, especie, raza y fecha.
                    Período: <span class="font-semibold">{{ $periodoTexto }}</span>
                </p>
            </x-vl-hero-heading>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ $this->excelUrl }}"
                   class="btn-secondary bg-white/10 text-white border-white/30 hover:bg-white/20">
                    Exportar Excel
                </a>
                <a href="{{ $this->pdfUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn-secondary bg-white/10 text-white border-white/30 hover:bg-white/20">
                    Exportar PDF
                </a>
            </div>
        </div>
    </div>

    <div class="vl-card overflow-hidden mb-4">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-lep-cliente">Cliente</label>
                <select wire:model.live="idClientes"
                        id="vl-lep-cliente"
                        class="form-input"
                        @disabled($clienteBloqueado)>
                    <option value="">Todos</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-lep-paciente">Paciente</label>
                <input wire:model.live.debounce.300ms="paciente"
                       id="vl-lep-paciente"
                       type="search"
                       placeholder="Nombre, propietario o protocolo…"
                       class="form-input">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-lep-especie">Especie</label>
                <select wire:model.live="idEspecies" id="vl-lep-especie" class="form-input">
                    <option value="">Todas</option>
                    @foreach ($especies as $especie)
                        <option value="{{ $especie->idEspecies }}">{{ $especie->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-lep-raza">Raza</label>
                <select wire:model.live="idRazas"
                        id="vl-lep-raza"
                        class="form-input"
                        @disabled(! $idEspecies)>
                    <option value="">Todas</option>
                    @foreach ($razas as $raza)
                        <option value="{{ $raza->idRazas }}">{{ $raza->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-lep-desde">Desde</label>
                <input type="date"
                       wire:model.live="fechaDesde"
                       id="vl-lep-desde"
                       class="form-input tabular-nums">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-lep-hasta">Hasta</label>
                <input type="date"
                       wire:model.live="fechaHasta"
                       id="vl-lep-hasta"
                       class="form-input tabular-nums">
            </div>
        </div>

        <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-neutral-700 cursor-pointer select-none">
                <input type="checkbox"
                       wire:model.live="agruparPorCliente"
                       class="rounded border-accent-300 text-primary-700 focus:ring-primary-500">
                Agrupar por cliente
            </label>
            <button type="button"
                    class="btn-secondary text-sm"
                    wire:click="limpiarFiltros">
                Limpiar filtros
            </button>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="vl-pacientes-grid min-w-full text-xs">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-pacientes-th vl-pacientes-th--num">#</th>
                        <th class="vl-pacientes-th">Clientes</th>
                        <th class="vl-pacientes-th">Especies</th>
                        <th class="vl-pacientes-th">Razas</th>
                        <th class="vl-pacientes-th">Fecha</th>
                        <th class="vl-pacientes-th">Protocolo</th>
                        <th class="vl-pacientes-th">Nombre</th>
                        <th class="vl-pacientes-th">Propietario</th>
                        <th class="vl-pacientes-th">Estado</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Precio</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Pagado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @if ($agruparPorCliente && $bloques !== null)
                        @php $nro = ($registros->currentPage() - 1) * $registros->perPage(); @endphp
                        @forelse ($bloques as $bloque)
                            @if ($bloque['tipo'] === 'grupo')
                                <tr class="bg-accent-100/70">
                                    <td colspan="9" class="vl-pacientes-td font-semibold text-neutral-800">
                                        {{ $bloque['cliente'] ?: 'Sin cliente' }}
                                        <span class="ml-2 text-xs font-normal text-neutral-500">
                                            ({{ $bloque['cantidad'] }} {{ $bloque['cantidad'] === 1 ? 'registro' : 'registros' }})
                                        </span>
                                    </td>
                                    <td class="vl-pacientes-td vl-pacientes-td--num font-semibold whitespace-nowrap tabular-nums">
                                        {{ \App\Support\Listados\ListadoEstadisticoPacientesConsulta::formatearMoneda((float) $bloque['subtotal_precio']) }}
                                    </td>
                                    <td class="vl-pacientes-td vl-pacientes-td--num font-semibold whitespace-nowrap tabular-nums">
                                        {{ \App\Support\Listados\ListadoEstadisticoPacientesConsulta::formatearMoneda((float) $bloque['subtotal_pagado']) }}
                                    </td>
                                </tr>
                            @else
                                @php
                                    $fila = $bloque['fila'];
                                    $nro++;
                                @endphp
                                <tr class="vl-pacientes-row hover:bg-accent-50/40" wire:key="lep-{{ $fila->idPacientes }}">
                                    <td class="vl-pacientes-td vl-pacientes-td--num">{{ $nro }}</td>
                                    <td class="vl-pacientes-td">{{ $fila->cliente ?: '—' }}</td>
                                    <td class="vl-pacientes-td">{{ $fila->especie ?: '—' }}</td>
                                    <td class="vl-pacientes-td">{{ $fila->raza ?: '—' }}</td>
                                    <td class="vl-pacientes-td whitespace-nowrap tabular-nums text-center">
                                        {{ $fila->fechhoy !== '' ? \Carbon\Carbon::parse($fila->fechhoy)->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="vl-pacientes-td font-semibold whitespace-nowrap">{{ $fila->nombreProtocolo ?: '—' }}</td>
                                    <td class="vl-pacientes-td">{{ $fila->nombre ?: '—' }}</td>
                                    <td class="vl-pacientes-td">{{ $fila->propietario ?: '—' }}</td>
                                    <td class="vl-pacientes-td whitespace-nowrap">{{ $fila->estado ?: '—' }}</td>
                                    <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">
                                        {{ \App\Support\Listados\ListadoEstadisticoPacientesConsulta::formatearMoneda((float) $fila->precio) }}
                                    </td>
                                    <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">
                                        {{ \App\Support\Listados\ListadoEstadisticoPacientesConsulta::formatearMoneda((float) $fila->pagado) }}
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="11" class="vl-pacientes-td text-center text-neutral-500 py-8">
                                    No hay protocolos con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    @else
                        @forelse ($registros as $fila)
                            <tr class="vl-pacientes-row hover:bg-accent-50/40" wire:key="lep-{{ $fila->idPacientes }}">
                                <td class="vl-pacientes-td vl-pacientes-td--num">
                                    {{ ($registros->currentPage() - 1) * $registros->perPage() + $loop->iteration }}
                                </td>
                                <td class="vl-pacientes-td">{{ $fila->cliente ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $fila->especie ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $fila->raza ?: '—' }}</td>
                                <td class="vl-pacientes-td whitespace-nowrap tabular-nums text-center">
                                    {{ $fila->fechhoy !== '' ? \Carbon\Carbon::parse($fila->fechhoy)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="vl-pacientes-td font-semibold whitespace-nowrap">{{ $fila->nombreProtocolo ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $fila->nombre ?: '—' }}</td>
                                <td class="vl-pacientes-td">{{ $fila->propietario ?: '—' }}</td>
                                <td class="vl-pacientes-td whitespace-nowrap">{{ $fila->estado ?: '—' }}</td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">
                                    {{ \App\Support\Listados\ListadoEstadisticoPacientesConsulta::formatearMoneda((float) $fila->precio) }}
                                </td>
                                <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">
                                    {{ \App\Support\Listados\ListadoEstadisticoPacientesConsulta::formatearMoneda((float) $fila->pagado) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="vl-pacientes-td text-center text-neutral-500 py-8">
                                    No hay protocolos con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
                @if ($registros->total() > 0)
                    <tfoot class="bg-accent-50/60 border-t border-accent-200">
                        <tr>
                            <td colspan="9" class="vl-pacientes-td text-right font-semibold">
                                Totales de la página ({{ $resumenPagina['cantidad'] }}):
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num font-semibold whitespace-nowrap">
                                {{ \App\Support\Listados\ListadoEstadisticoPacientesConsulta::formatearMoneda((float) $resumenPagina['total_precio']) }}
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num font-semibold whitespace-nowrap">
                                {{ \App\Support\Listados\ListadoEstadisticoPacientesConsulta::formatearMoneda((float) $resumenPagina['total_pagado']) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if ($registros->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $registros->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>
</div>
