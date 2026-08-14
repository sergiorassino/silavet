<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Listados estadísticos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Determinaciones por cliente</h1>
                <p class="mt-2 text-sm text-white/80">
                    Determinaciones pedidas agrupadas por cliente veterinario, con cantidad y suma de precios.
                    Período: <span class="font-semibold">{{ $periodoTexto }}</span>
                </p>
            </x-vl-hero-heading>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ $this->excelUrl }}"
                   class="btn-secondary bg-white/10 text-white border-white/30 hover:bg-white/20">
                    Exportar Excel
                </a>
            </div>
        </div>
    </div>

    <div class="vl-card overflow-hidden mb-4">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-dpc-cliente">Cliente</label>
                <select wire:model.live="idClientes"
                        id="vl-dpc-cliente"
                        class="form-input"
                        @disabled($clienteBloqueado)>
                    <option value="">Todos</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-dpc-busqueda">Búsqueda rápida</label>
                <input wire:model.live.debounce.300ms="busqueda"
                       id="vl-dpc-busqueda"
                       type="search"
                       placeholder="Cliente, determinación, protocolo o paciente…"
                       class="form-input"
                       autocomplete="off">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-dpc-desde">Desde</label>
                <input type="date"
                       wire:model.live="fechaDesde"
                       id="vl-dpc-desde"
                       class="form-input tabular-nums">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-dpc-hasta">Hasta</label>
                <input type="date"
                       wire:model.live="fechaHasta"
                       id="vl-dpc-hasta"
                       class="form-input tabular-nums">
            </div>
        </div>

        <div class="px-5 py-3 flex flex-wrap items-center justify-end gap-3">
            <button type="button"
                    class="btn-secondary text-sm"
                    wire:click="limpiarFiltros">
                Limpiar filtros
            </button>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="divide-y divide-accent-100">
            @forelse ($grupos as $grupo)
                @php
                    $idGrupo = (int) $grupo->idClientes;
                    $abierto = in_array($idGrupo, $expandidos, true);
                    $filasGrupo = $detalles[$idGrupo] ?? [];
                @endphp
                <div wire:key="dpc-grupo-{{ $idGrupo }}">
                    <button type="button"
                            class="w-full px-4 py-3 flex items-start gap-3 text-left hover:bg-accent-50/60"
                            wire:click="toggleCliente({{ $idGrupo }})"
                            wire:loading.class="opacity-60"
                            wire:target="toggleCliente({{ $idGrupo }})"
                            aria-expanded="{{ $abierto ? 'true' : 'false' }}">
                        <span class="mt-1 shrink-0 text-neutral-500" aria-hidden="true">
                            <svg class="h-4 w-4 transition-transform {{ $abierto ? 'rotate-90' : '' }}"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-neutral-800">
                                Cliente {{ $grupo->cliente !== '' ? $grupo->cliente : 'Sin cliente' }}
                            </span>
                            <span class="block text-xs text-neutral-500 mt-0.5">
                                Cantidad {{ $grupo->cantidad }}
                                <span class="mx-1.5 text-neutral-300">·</span>
                                Suma $ {{ \App\Support\Listados\DeterminacionesPorClienteConsulta::formatearMoneda((float) $grupo->sumaPrecio) }}
                            </span>
                        </span>
                    </button>

                    @if ($abierto)
                        <div class="overflow-x-auto border-t border-accent-100">
                            <table class="vl-pacientes-grid min-w-full text-xs">
                                <thead class="bg-accent-50/80">
                                    <tr>
                                        <th class="vl-pacientes-th">Cliente</th>
                                        <th class="vl-pacientes-th">Determinación</th>
                                        <th class="vl-pacientes-th">Fecha</th>
                                        <th class="vl-pacientes-th">Protocolo</th>
                                        <th class="vl-pacientes-th">Paciente</th>
                                        <th class="vl-pacientes-th vl-pacientes-th--num">Precio</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-accent-100">
                                    @forelse ($filasGrupo as $fila)
                                        <tr class="vl-pacientes-row hover:bg-accent-50/40" wire:key="dpc-fila-{{ $fila->idDeterminaciones }}">
                                            <td class="vl-pacientes-td">{{ $fila->cliente !== '' ? $fila->cliente : '—' }}</td>
                                            <td class="vl-pacientes-td">{{ $fila->determinacion !== '' ? $fila->determinacion : '—' }}</td>
                                            <td class="vl-pacientes-td whitespace-nowrap tabular-nums text-center">
                                                {{ $fila->fechhoy !== '' ? \Carbon\Carbon::parse($fila->fechhoy)->format('d/m/Y') : '—' }}
                                            </td>
                                            <td class="vl-pacientes-td font-semibold whitespace-nowrap">{{ $fila->protocolo !== '' ? $fila->protocolo : '—' }}</td>
                                            <td class="vl-pacientes-td">{{ $fila->paciente !== '' ? $fila->paciente : '—' }}</td>
                                            <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap tabular-nums">
                                                {{ \App\Support\Listados\DeterminacionesPorClienteConsulta::formatearMoneda((float) $fila->precio) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="vl-pacientes-td text-center text-neutral-500 py-6">
                                                No hay determinaciones en este grupo con los filtros seleccionados.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-neutral-100/80 border-t border-accent-200">
                                    <tr>
                                        <td colspan="5" class="vl-pacientes-td font-semibold">Suma</td>
                                        <td class="vl-pacientes-td vl-pacientes-td--num font-semibold whitespace-nowrap tabular-nums">
                                            {{ \App\Support\Listados\DeterminacionesPorClienteConsulta::formatearMoneda((float) $grupo->sumaPrecio) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-neutral-500">
                    No hay determinaciones con los filtros seleccionados.
                </p>
            @endforelse
        </div>

        @if ($grupos->total() > 0)
            <div class="px-4 py-3 bg-accent-50/60 border-t border-accent-200 flex flex-wrap items-center justify-between gap-2 text-xs font-semibold text-neutral-700">
                <span>
                    Totales de la página ({{ $resumenPagina['cantidad_grupos'] }} {{ $resumenPagina['cantidad_grupos'] === 1 ? 'cliente' : 'clientes' }},
                    {{ $resumenPagina['cantidad'] }} {{ $resumenPagina['cantidad'] === 1 ? 'determinación' : 'determinaciones' }}):
                </span>
                <span class="tabular-nums">
                    $ {{ \App\Support\Listados\DeterminacionesPorClienteConsulta::formatearMoneda((float) $resumenPagina['total_precio']) }}
                </span>
            </div>
        @endif

        @if ($grupos->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $grupos->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>
</div>
