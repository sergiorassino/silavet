<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="vl-eyebrow">Listados estadísticos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Historial de determinaciones</h1>
                <p class="mt-2 text-sm text-white/80">
                    Resultados de renglones filtrables por cliente, paciente, especie, protocolo, grupo, determinación y valor.
                    Período: <span class="font-semibold">{{ $periodoTexto }}</span>
                </p>
            </div>
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
        <div class="vl-toolbar border-b border-accent-200 px-5 py-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-hd-cliente">Cliente</label>
                <select wire:model.live="idClientes"
                        id="vl-hd-cliente"
                        class="form-input"
                        @disabled($clienteBloqueado)>
                    <option value="">Todos</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-hd-paciente">Paciente</label>
                <input wire:model.live.debounce.300ms="paciente"
                       id="vl-hd-paciente"
                       type="search"
                       placeholder="Nombre o propietario…"
                       class="form-input">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-hd-especie">Especie</label>
                <select wire:model.live="idEspecies" id="vl-hd-especie" class="form-input">
                    <option value="">Todas</option>
                    @foreach ($especies as $especie)
                        <option value="{{ $especie->idEspecies }}">{{ $especie->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-hd-protocolo">Protocolo</label>
                <input wire:model.live.debounce.300ms="protocolo"
                       id="vl-hd-protocolo"
                       type="search"
                       placeholder="Nº de protocolo…"
                       class="form-input tabular-nums"
                       autocomplete="off">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-hd-grupo">Grupo</label>
                <select wire:model.live="idGrupos" id="vl-hd-grupo" class="form-input">
                    <option value="">Todos</option>
                    @foreach ($grupos as $grupo)
                        <option value="{{ $grupo->idGrupos }}">{{ $grupo->nombreGrupo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-hd-determinacion">Determinación</label>
                <select wire:model.live="idsItems"
                        id="vl-hd-determinacion"
                        class="form-input min-h-[6.5rem]"
                        multiple
                        size="4">
                    @foreach ($determinaciones as $item)
                        <option value="{{ $item->idItems }}">{{ $item->nombreItem }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-neutral-500">Ctrl/Cmd + clic para varias.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-hd-valor-op">Valor</label>
                <div class="flex flex-col gap-2">
                    <select wire:model.live="valorOperador" id="vl-hd-valor-op" class="form-input">
                        <option value="">Sin filtro</option>
                        <option value="=">Igual a</option>
                        <option value=">">Mayor que</option>
                        <option value=">=">Mayor o igual</option>
                        <option value="<">Menor que</option>
                        <option value="<=">Menor o igual</option>
                        <option value="entre">Entre</option>
                    </select>
                    <input wire:model.live.debounce.300ms="valor"
                           id="vl-hd-valor"
                           type="text"
                           inputmode="decimal"
                           placeholder="{{ $valorOperador === 'entre' ? 'Desde…' : 'Valor…' }}"
                           class="form-input tabular-nums"
                           @disabled($valorOperador === '')>
                    @if ($valorOperador === 'entre')
                        <input wire:model.live.debounce.300ms="valorHasta"
                               id="vl-hd-valor-hasta"
                               type="text"
                               inputmode="decimal"
                               placeholder="Hasta…"
                               class="form-input tabular-nums">
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-hd-desde">Desde</label>
                    <input type="date"
                           wire:model.live="fechaDesde"
                           id="vl-hd-desde"
                           class="form-input tabular-nums">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-hd-hasta">Hasta</label>
                    <input type="date"
                           wire:model.live="fechaHasta"
                           id="vl-hd-hasta"
                           class="form-input tabular-nums">
                </div>
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
        <div class="overflow-x-auto">
            <table class="vl-pacientes-grid min-w-full text-xs">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-pacientes-th">Fecha</th>
                        <th class="vl-pacientes-th">Cliente</th>
                        <th class="vl-pacientes-th">Protocolo</th>
                        <th class="vl-pacientes-th">Paciente</th>
                        <th class="vl-pacientes-th">Especie</th>
                        <th class="vl-pacientes-th">Grupo</th>
                        <th class="vl-pacientes-th">Determinación</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @forelse ($registros as $fila)
                        <tr class="vl-pacientes-row hover:bg-accent-50/40" wire:key="hd-{{ $fila->idRenglones }}">
                            <td class="vl-pacientes-td whitespace-nowrap tabular-nums text-center">
                                {{ $fila->fechhoy !== '' ? \Carbon\Carbon::parse($fila->fechhoy)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="vl-pacientes-td">{{ $fila->cliente ?: '—' }}</td>
                            <td class="vl-pacientes-td font-semibold whitespace-nowrap">{{ $fila->protocolo ?: '—' }}</td>
                            <td class="vl-pacientes-td">{{ $fila->paciente ?: '—' }}</td>
                            <td class="vl-pacientes-td">{{ $fila->especie ?: '—' }}</td>
                            <td class="vl-pacientes-td uppercase">{{ $fila->grupo ?: '—' }}</td>
                            <td class="vl-pacientes-td">{{ $fila->determinacion ?: '—' }}</td>
                            <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap tabular-nums">
                                {{ $fila->valor !== '' ? $fila->valor : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="vl-pacientes-td text-center text-neutral-500 py-8">
                                No hay determinaciones con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($registros->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $registros->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>
</div>
