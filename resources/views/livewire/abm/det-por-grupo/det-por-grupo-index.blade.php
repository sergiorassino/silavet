<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Administración</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Det. por Grupo (Inf)</h1>
                <p class="mt-2 max-w-2xl text-sm text-white/80">
                    Parametrice qué ítems del informe incluye cada tipo de determinación y en qué orden aparecen.
                </p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-det-grupo-layout">
        {{-- Panel padre: determinaciones --}}
        <aside class="vl-card vl-det-grupo-master overflow-hidden">
            <div class="border-b border-accent-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-neutral-800">Determinaciones</h2>
                <p class="mt-0.5 text-xs text-neutral-500">Seleccione una para editar su plantilla.</p>
            </div>
            <div class="vl-toolbar border-b border-accent-200 px-3 py-2">
                <input wire:model.live="busquedaDeterminacion"
                       type="search"
                       placeholder="Buscar por orden o nombre…"
                       class="form-input w-full py-1.5 text-sm">
            </div>
            <div class="vl-det-grupo-master-sort flex items-center gap-2 border-b border-accent-200 px-3 py-1.5">
                <button type="button"
                        wire:click="$set('ordenListadoDeterminacion', 'orden')"
                        class="vl-det-grupo-master-sort-btn w-8 shrink-0 {{ $ordenListadoDeterminacion === 'orden' ? 'is-active' : '' }}"
                        title="Ordenar por número de orden">
                    Ord.
                </button>
                <div class="flex min-w-0 flex-1 items-baseline gap-1.5">
                    <button type="button"
                            wire:click="$set('ordenListadoDeterminacion', 'nombre')"
                            class="vl-det-grupo-master-sort-btn shrink-0 {{ $ordenListadoDeterminacion === 'nombre' ? 'is-active' : '' }}"
                            title="Ordenar alfabéticamente por nombre">
                        Nombre
                    </button>
                    <span class="vl-det-grupo-master-sort-hint leading-tight">(click en la cabecera para reordenar)</span>
                </div>
                <span class="w-[3.25rem] shrink-0" aria-hidden="true"></span>
            </div>
            <div class="vl-det-grupo-master-list">
                @forelse ($determinaciones as $det)
                    <button type="button"
                            wire:key="det-{{ $det->idTipodeterminaciones }}"
                            wire:click="seleccionarDeterminacion({{ $det->idTipodeterminaciones }})"
                            class="vl-det-grupo-master-item {{ (int) $idDeterminacionSeleccionada === (int) $det->idTipodeterminaciones ? 'is-active' : '' }}">
                        <span class="vl-det-grupo-master-orden tabular-nums">{{ $det->orden ?? '—' }}</span>
                        <span class="min-w-0 flex-1 truncate text-left font-medium">{{ $det->nombre }}</span>
                        <span class="vl-pill shrink-0 text-[10px]">{{ $det->renglones_plantilla_count }} ítems</span>
                    </button>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-neutral-500">No hay determinaciones registradas.</p>
                @endforelse
            </div>
        </aside>

        {{-- Panel hijo: plantilla del informe --}}
        <section class="vl-card vl-det-grupo-detail overflow-hidden">
            @if ($determinacionActiva)
                <div class="flex flex-col gap-3 border-b border-accent-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary-600">Plantilla del informe</p>
                        <h2 class="truncate text-lg font-bold text-neutral-900">{{ $determinacionActiva->nombre }}</h2>
                        <p class="text-xs text-neutral-500">
                            {{ count($idsRenglonesVisibles) }} renglón{{ count($idsRenglonesVisibles) === 1 ? '' : 'es' }} configurados
                        </p>
                    </div>
                    <button type="button"
                            wire:click="abrirModalAgregar"
                            class="btn-primary shrink-0 self-start sm:self-center">
                        Agregar ítem
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="vl-det-grupo-grid text-sm">
                        <thead class="bg-accent-50/80">
                            <tr>
                                <th class="vl-det-grupo-th vl-det-grupo-col--reordenar">Reordenar</th>
                                <th class="vl-det-grupo-th vl-det-grupo-col--acciones" title="Acciones"></th>
                                <th class="vl-det-grupo-th vl-det-grupo-col--orden">Orden</th>
                                <th class="vl-det-grupo-th vl-det-grupo-col--grupo">Grupo</th>
                                <th class="vl-det-grupo-th vl-det-grupo-col--item">Ítem del informe</th>
                                <th class="vl-det-grupo-th vl-det-grupo-col--unidad">Unidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-accent-100">
                            @forelse ($idsRenglonesVisibles as $idRenglon)
                                <tr class="vl-det-grupo-row"
                                    wire:key="rxd-{{ $idRenglon }}-{{ (int) ($filasRenglon[$idRenglon]['orden'] ?? 0) }}">
                                    <td class="vl-det-grupo-td vl-det-grupo-col--reordenar">
                                        <div class="flex items-center justify-center gap-0.5">
                                            <x-vl-grid-icon-btn title="Subir"
                                                                wire:click="moverRenglon({{ $idRenglon }}, 'arriba')">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            </x-vl-grid-icon-btn>
                                            <x-vl-grid-icon-btn title="Bajar"
                                                                wire:click="moverRenglon({{ $idRenglon }}, 'abajo')">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </x-vl-grid-icon-btn>
                                        </div>
                                    </td>
                                    <td class="vl-det-grupo-td vl-det-grupo-col--acciones">
                                        <div class="flex items-center justify-center gap-0.5">
                                            <x-vl-grid-icon-btn title="Eliminar"
                                                                variant="danger"
                                                                x-on:click="window.vlSwalConfirmar('¿Quitar este ítem de la plantilla?', 'Eliminar renglón', { confirmButtonText: 'Sí, quitar', icon: 'warning' }).then(ok => ok && $wire.eliminarRenglon({{ $idRenglon }}))">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </x-vl-grid-icon-btn>
                                            <x-vl-grid-icon-btn title="Guardar fila"
                                                                variant="primary"
                                                                wire:click="guardarRenglon({{ $idRenglon }})">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </x-vl-grid-icon-btn>
                                            <x-vl-grid-icon-btn title="Descartar cambios"
                                                                wire:click="descartarRenglon({{ $idRenglon }})">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                          d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                                </svg>
                                            </x-vl-grid-icon-btn>
                                        </div>
                                    </td>
                                    <td class="vl-det-grupo-td vl-det-grupo-col--orden">
                                        <input type="text"
                                               wire:model.blur="filasRenglon.{{ $idRenglon }}.orden"
                                               class="vl-det-grupo-input vl-det-grupo-input--orden"
                                               inputmode="numeric">
                                    </td>
                                    <td class="vl-det-grupo-td vl-det-grupo-col--grupo">
                                        <span class="vl-det-grupo-grupo-text">{{ $filasRenglon[$idRenglon]['nombre_grupo'] ?? '—' }}</span>
                                    </td>
                                    <td class="vl-det-grupo-td vl-det-grupo-col--item">
                                        <span class="font-medium text-neutral-900">{{ $filasRenglon[$idRenglon]['nombre_item'] ?? '—' }}</span>
                                    </td>
                                    <td class="vl-det-grupo-td vl-det-grupo-col--unidad">
                                        <span class="text-neutral-600">{{ $filasRenglon[$idRenglon]['unidad'] ?: '—' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="vl-det-grupo-td py-10 text-center text-neutral-500">
                                        <p class="font-medium">Esta determinación aún no tiene ítems en el informe.</p>
                                        <p class="mt-1 text-xs">Use <strong>Agregar ítem</strong> para armar la plantilla.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex min-h-[16rem] items-center justify-center px-6 py-12 text-center text-neutral-500">
                    <div>
                        @if (trim($busquedaDeterminacion) !== '')
                            <p class="font-medium">Seleccione una determinación del listado filtrado</p>
                            <p class="mt-1 text-sm">La plantilla se mostrará al elegir un resultado de la búsqueda.</p>
                        @else
                            <p class="font-medium">Seleccione una determinación del panel izquierdo</p>
                            <p class="mt-1 text-sm">Allí podrá ver y editar los renglones de su informe.</p>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>

    @if ($modalAgregarAbierto)
        @teleport('body')
            <div class="fixed inset-0 z-[120] flex items-end justify-center p-4 sm:items-center"
                 wire:keydown.escape.window="cerrarModalAgregar">
                <button type="button"
                        class="absolute inset-0 bg-neutral-900/50"
                        wire:click="cerrarModalAgregar"
                        aria-label="Cerrar"></button>
                <div class="relative z-10 flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-agregar-item-titulo">
                    <div class="border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-agregar-item-titulo" class="text-lg font-bold text-neutral-900">Agregar ítem al informe</h3>
                        @if ($determinacionActiva)
                            <p class="mt-1 text-sm text-neutral-600">
                                Determinación: <strong>{{ $determinacionActiva->nombre }}</strong>
                            </p>
                        @endif
                    </div>
                    <div class="border-b border-accent-200 px-5 py-3">
                        <input wire:model.live.debounce.300ms="busquedaItem"
                               type="search"
                               placeholder="Buscar por nombre de ítem o grupo…"
                               class="form-input w-full"
                               autofocus>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
                        @php
                            $grupoActual = null;
                        @endphp
                        @forelse ($itemsDisponibles as $item)
                            @if ($grupoActual !== (int) ($item->idGrupos ?? 0))
                                @php $grupoActual = (int) ($item->idGrupos ?? 0); @endphp
                                <p class="sticky top-0 z-[1] bg-white px-3 py-2 text-[10px] font-bold uppercase tracking-[0.08em] text-primary-700">
                                    {{ $item->grupo?->nombreGrupo ?? 'Sin grupo' }}
                                </p>
                            @endif
                            <button type="button"
                                    wire:key="item-add-{{ $item->idItems }}"
                                    wire:click="agregarItem({{ $item->idItems }})"
                                    class="vl-det-grupo-item-picker">
                                <span class="min-w-0 flex-1 truncate text-left font-medium text-neutral-900">{{ $item->nombreItem }}</span>
                                @if ($item->unidadMedida)
                                    <span class="shrink-0 text-xs text-neutral-500">{{ $item->unidadMedida }}</span>
                                @endif
                                <svg class="h-4 w-4 shrink-0 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-neutral-500">
                                @if (trim($busquedaItem) !== '')
                                    No hay ítems que coincidan con la búsqueda o ya están en la plantilla.
                                @else
                                    Todos los ítems disponibles ya están en esta plantilla.
                                @endif
                            </p>
                        @endforelse
                    </div>
                    <div class="flex justify-end border-t border-accent-200 px-5 py-3">
                        <button type="button"
                                wire:click="cerrarModalAgregar"
                                class="rounded-xl border border-accent-200 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-accent-50">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
