<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Gestión de Stock</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Reactivos e Insumos por Determinación</h1>
                <p class="mt-2 max-w-2xl text-sm text-white/80">
                    Configure qué reactivos e insumos consume cada tipo de determinación y en qué cantidad.
                </p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-det-grupo-layout">
        {{-- Panel izquierdo: determinaciones --}}
        <aside class="vl-card vl-det-grupo-master overflow-hidden">
            <div class="border-b border-accent-200 px-4 py-3">
                <h2 class="text-sm font-semibold text-neutral-800">Determinaciones</h2>
                <p class="mt-0.5 text-xs text-neutral-500">Seleccione una para ver sus reactivos.</p>
            </div>
            <div class="vl-toolbar border-b border-accent-200 px-3 py-2">
                <input wire:model.live="busquedaDeterminacion"
                       type="search"
                       placeholder="Buscar determinación…"
                       class="form-input w-full py-1.5 text-sm">
            </div>
            <div class="vl-det-grupo-master-list">
                @forelse ($determinaciones as $det)
                    <button type="button"
                            wire:key="det-{{ $det->idTipodeterminaciones }}"
                            wire:click="seleccionarDeterminacion({{ $det->idTipodeterminaciones }})"
                            class="vl-det-grupo-master-item {{ (int) $idDeterminacionSeleccionada === (int) $det->idTipodeterminaciones ? 'is-active' : '' }}">
                        <span class="vl-det-grupo-master-orden tabular-nums">{{ $det->orden ?? '—' }}</span>
                        <span class="min-w-0 flex-1 truncate text-left font-medium">{{ $det->nombre }}</span>
                        <span class="vl-pill shrink-0 text-[10px]">{{ $det->consumos_reactivos_count }}</span>
                    </button>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-neutral-500">No hay determinaciones.</p>
                @endforelse
            </div>
        </aside>

        {{-- Panel derecho: reactivos de la determinación --}}
        <section class="vl-card vl-det-grupo-detail overflow-hidden">
            @if ($determinacionActiva)
                <div class="flex flex-col gap-3 border-b border-accent-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary-600">Reactivos que utiliza</p>
                        <h2 class="truncate text-lg font-bold text-neutral-900">{{ $determinacionActiva->nombre }}</h2>
                        <p class="text-xs text-neutral-500">{{ count($filas) }} reactivo{{ count($filas) === 1 ? '' : 's' }} configurados</p>
                    </div>
                    <button type="button"
                            wire:click="abrirModalAgregar"
                            class="btn-primary shrink-0 self-start sm:self-center">
                        Nuevo
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="vl-det-grupo-grid text-sm" style="width:auto">
                        <thead class="bg-accent-50/80">
                            <tr>
                                <th class="vl-det-grupo-th vl-det-grupo-col--acciones" title="Acciones"></th>
                                <th class="vl-det-grupo-th text-left">Reactivo</th>
                                <th class="vl-det-grupo-th text-right" style="width:120px">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-accent-100">
                            @forelse ($filas as $idFila => $fila)
                                <tr class="vl-det-grupo-row" wire:key="rxd-{{ $idFila }}">
                                    <td class="vl-det-grupo-td vl-det-grupo-col--acciones">
                                        <div class="flex items-center justify-center gap-0.5">
                                            @if ((int) $filaEditandoId === $idFila)
                                                <x-vl-grid-icon-btn title="Guardar cantidad"
                                                                    variant="primary"
                                                                    wire:click="guardarCantidad({{ $idFila }})">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </x-vl-grid-icon-btn>
                                                <x-vl-grid-icon-btn title="Cancelar"
                                                                    wire:click="cancelarEdicion">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </x-vl-grid-icon-btn>
                                            @else
                                                <x-vl-grid-icon-btn title="Editar cantidad"
                                                                    wire:click="editarFila({{ $idFila }})">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </x-vl-grid-icon-btn>
                                                <x-vl-grid-icon-btn title="Quitar reactivo"
                                                                    variant="danger"
                                                                    x-on:click="window.vlSwalConfirmar('¿Quitar este reactivo de la determinación?', 'Quitar reactivo', { confirmButtonText: 'Sí, quitar', icon: 'warning' }).then(ok => ok && $wire.eliminarFila({{ $idFila }}))">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </x-vl-grid-icon-btn>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="vl-det-grupo-td font-medium text-neutral-900">
                                        {{ $fila['nombre'] }}
                                    </td>
                                    <td class="vl-det-grupo-td text-right">
                                        @if ((int) $filaEditandoId === $idFila)
                                            <input type="text"
                                                   wire:model="filas.{{ $idFila }}.cantidad"
                                                   class="vl-det-grupo-input vl-det-grupo-input--orden text-right"
                                                   inputmode="decimal"
                                                   autocomplete="off">
                                        @else
                                            <span class="tabular-nums">{{ $fila['cantidad'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="vl-det-grupo-td py-10 text-center text-neutral-500">
                                        <p class="font-medium">Esta determinación no tiene reactivos configurados.</p>
                                        <p class="mt-1 text-xs">Use <strong>Nuevo</strong> para agregar reactivos e insumos.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (! empty($filas))
                    <div class="border-t border-accent-100 px-4 py-2 text-right text-xs text-neutral-500">
                        [1 a {{ count($filas) }} de {{ count($filas) }}]
                    </div>
                @endif
            @else
                <div class="flex min-h-[16rem] items-center justify-center px-6 py-12 text-center text-neutral-500">
                    <div>
                        <p class="font-medium">Seleccione una determinación del panel izquierdo</p>
                        <p class="mt-1 text-sm">Allí podrá ver y configurar qué reactivos consume cada determinación.</p>
                    </div>
                </div>
            @endif
        </section>
    </div>

    {{-- Modal agregar reactivo --}}
    @if ($modalAgregarAbierto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/50 p-4"
             x-data
             @keydown.escape.window="$wire.cerrarModalAgregar()">
            <div class="vl-card w-full max-w-sm p-4" @click.stop>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-neutral-800">Agregar reactivo</h3>
                    <button type="button"
                            wire:click="cerrarModalAgregar"
                            class="text-neutral-400 hover:text-neutral-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="grid gap-3">
                    <div>
                        <label class="form-label mb-1" for="nuevoIdReactivo">Reactivo *</label>
                        <select wire:model="nuevoIdReactivo"
                                id="nuevoIdReactivo"
                                class="form-input py-1.5 text-sm">
                            <option value="0">— Seleccione —</option>
                            @foreach ($reactivosDisponibles as $r)
                                <option value="{{ $r->id }}">{{ $r->reactivo }}</option>
                            @endforeach
                        </select>
                        @error('idReactivos') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label mb-1" for="nuevaCantidad">Cantidad por determinación *</label>
                        <input wire:model="nuevaCantidad"
                               id="nuevaCantidad"
                               type="text"
                               class="form-input py-1.5 text-sm"
                               inputmode="decimal"
                               autocomplete="off"
                               placeholder="1.0000">
                        @error('cantidad') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="mt-1 text-[11px] text-neutral-500">Hasta 4 decimales (ej: 0.5000).</p>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button"
                                wire:click="agregarReactivo"
                                wire:loading.attr="disabled"
                                wire:target="agregarReactivo"
                                class="btn-primary py-1.5 text-sm">
                            <span wire:loading.remove wire:target="agregarReactivo">Agregar</span>
                            <span wire:loading wire:target="agregarReactivo">Agregando…</span>
                        </button>
                        <button type="button"
                                wire:click="cerrarModalAgregar"
                                class="btn-secondary py-1.5 text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
