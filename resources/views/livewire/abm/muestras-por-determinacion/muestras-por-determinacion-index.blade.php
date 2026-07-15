<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Procedimientos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Muestras por Determinación</h1>
                <p class="mt-2 max-w-2xl text-sm text-white/80">
                    Asocie a cada procedimiento de toma de muestra las determinaciones que lo requieren.
                    Estimación de costos reunirá estos vínculos automáticamente.
                </p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-det-grupo-layout">
        {{-- Panel padre: procedimientos (requerimientos) --}}
        <aside class="vl-card vl-det-grupo-master overflow-hidden">
            <div class="border-b border-accent-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-neutral-800">Procedimientos</h2>
                <p class="mt-0.5 text-xs text-neutral-500">Seleccione uno para ver sus determinaciones.</p>
            </div>
            <div class="vl-toolbar border-b border-accent-200 px-3 py-2">
                <input wire:model.live.debounce.300ms="busquedaProcedimiento"
                       type="search"
                       placeholder="Buscar por título o texto…"
                       class="form-input w-full py-1.5 text-sm">
            </div>
            <div class="vl-det-grupo-master-list">
                @forelse ($procedimientos as $proc)
                    <button type="button"
                            wire:key="req-{{ $proc->id }}"
                            wire:click="seleccionarProcedimiento({{ $proc->id }})"
                            class="vl-det-grupo-master-item {{ (int) $idRequerimientoSeleccionado === (int) $proc->id ? 'is-active' : '' }}">
                        <span class="min-w-0 flex-1 truncate text-left font-medium">
                            {{ $proc->titulo !== '' ? $proc->titulo : 'Sin título #'.$proc->id }}
                        </span>
                        <span class="vl-pill shrink-0 text-[10px]">{{ $proc->vinculos_tipodeterminacion_count }} det.</span>
                    </button>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-neutral-500">
                        No hay procedimientos registrados.
                        <a href="{{ route('abm.requerimientos.create') }}" class="mt-2 block text-primary-700 underline">Crear procedimiento</a>
                    </p>
                @endforelse
            </div>
        </aside>

        {{-- Panel hijo: determinaciones asociadas --}}
        <section class="vl-card vl-det-grupo-detail overflow-hidden">
            @if ($procedimientoActivo)
                <div class="flex flex-col gap-3 border-b border-accent-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary-600">Determinaciones asociadas</p>
                        <h2 class="truncate text-lg font-bold text-neutral-900">
                            {{ $procedimientoActivo->titulo !== '' ? $procedimientoActivo->titulo : 'Sin título #'.$procedimientoActivo->id }}
                        </h2>
                        <p class="text-xs text-neutral-500">
                            {{ count($vinculos) }} determinación{{ count($vinculos) === 1 ? '' : 'es' }} vinculada{{ count($vinculos) === 1 ? '' : 's' }}
                        </p>
                    </div>
                    <button type="button"
                            wire:click="abrirModalAgregar"
                            class="btn-primary shrink-0 self-start sm:self-center">
                        Agregar determinación
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="vl-det-grupo-grid text-sm">
                        <thead class="bg-accent-50/80">
                            <tr>
                                <th class="vl-det-grupo-th vl-det-grupo-col--acciones" title="Acciones"></th>
                                <th class="vl-det-grupo-th vl-det-grupo-col--orden">Orden</th>
                                <th class="vl-det-grupo-th">Tipo de análisis</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-accent-100">
                            @forelse ($vinculos as $fila)
                                <tr class="vl-det-grupo-row" wire:key="rxtd-{{ $fila['id'] }}">
                                    <td class="vl-det-grupo-td vl-det-grupo-col--acciones">
                                        <div class="flex items-center justify-center">
                                            <x-vl-grid-icon-btn title="Quitar asociación"
                                                                variant="danger"
                                                                x-on:click="window.vlSwalConfirmar('¿Quitar esta determinación del procedimiento?', 'Desasociar', { confirmButtonText: 'Sí, quitar', icon: 'warning' }).then(ok => ok && $wire.quitarVinculo({{ $fila['id'] }}))">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </x-vl-grid-icon-btn>
                                        </div>
                                    </td>
                                    <td class="vl-det-grupo-td vl-det-grupo-col--orden">
                                        <span class="tabular-nums text-neutral-600">{{ $fila['orden'] }}</span>
                                    </td>
                                    <td class="vl-det-grupo-td">
                                        <span class="font-medium text-neutral-900">{{ $fila['nombre'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="vl-det-grupo-td py-10 text-center text-neutral-500">
                                        <p class="font-medium">Este procedimiento aún no tiene determinaciones asociadas.</p>
                                        <p class="mt-1 text-xs">Use <strong>Agregar determinación</strong> para vincular tipos de análisis.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex min-h-[16rem] items-center justify-center px-6 py-12 text-center text-neutral-500">
                    <div>
                        @if (trim($busquedaProcedimiento) !== '')
                            <p class="font-medium">Seleccione un procedimiento del listado filtrado</p>
                            <p class="mt-1 text-sm">Las determinaciones asociadas se mostrarán al elegir un resultado.</p>
                        @else
                            <p class="font-medium">Seleccione un procedimiento del panel izquierdo</p>
                            <p class="mt-1 text-sm">Allí podrá ver y editar las determinaciones que lo requieren.</p>
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
                     aria-labelledby="modal-agregar-det-titulo">
                    <div class="border-b border-accent-200 px-5 py-4">
                        <h3 id="modal-agregar-det-titulo" class="text-lg font-bold text-neutral-900">Agregar determinación</h3>
                        @if ($procedimientoActivo)
                            <p class="mt-1 text-sm text-neutral-600">
                                Procedimiento:
                                <strong>{{ $procedimientoActivo->titulo !== '' ? $procedimientoActivo->titulo : 'Sin título #'.$procedimientoActivo->id }}</strong>
                            </p>
                        @endif
                    </div>
                    <div class="border-b border-accent-200 px-5 py-3">
                        <input wire:model.live.debounce.300ms="busquedaDeterminacion"
                               type="search"
                               placeholder="Buscar por orden o nombre…"
                               class="form-input w-full"
                               autofocus>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
                        @forelse ($determinacionesDisponibles as $det)
                            <button type="button"
                                    wire:key="det-add-{{ $det->idTipodeterminaciones }}"
                                    wire:click="agregarDeterminacion({{ $det->idTipodeterminaciones }})"
                                    class="vl-det-grupo-item-picker">
                                <span class="w-10 shrink-0 tabular-nums text-xs text-neutral-500">{{ $det->orden }}</span>
                                <span class="min-w-0 flex-1 truncate text-left font-medium text-neutral-900">{{ $det->nombre }}</span>
                                <svg class="h-4 w-4 shrink-0 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-neutral-500">
                                @if (trim($busquedaDeterminacion) !== '')
                                    No hay determinaciones que coincidan con la búsqueda o ya están asociadas.
                                @else
                                    Todas las determinaciones disponibles ya están asociadas a este procedimiento.
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
