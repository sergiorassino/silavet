<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Gestión de Stock</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Reactivos e Insumos</h1>
                <p class="mt-2 text-sm text-white/80">Control de stock de reactivos por cantidad restante, mínimo de aviso y existencia ideal.</p>
            </x-vl-hero-heading>
            <a href="{{ route('abm.reactivos.create') }}" class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">Nuevo reactivo</a>
        </div>
    </div>

    <div class="vl-card mx-auto max-w-5xl overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-3 py-2">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar reactivo…"
                   class="form-input max-w-xs py-1.5 text-sm">
        </div>

        <div class="flex justify-center overflow-x-auto px-2 py-1">
            <table class="vl-especies-grid text-sm" style="width:auto">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-especies-th vl-especies-col--acciones" title="Acciones"></th>
                        <th class="vl-especies-th text-left">Reactivo</th>
                        <th class="vl-especies-th text-right">Cantidad restante</th>
                        <th class="vl-especies-th text-right">Mínimo para aviso</th>
                        <th class="vl-especies-th text-right">Existencia ideal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @forelse ($reactivos as $reactivo)
                        @php
                            $bajominimo = $reactivo->cantidad <= $reactivo->minAviso;
                        @endphp
                        <tr class="vl-especies-row {{ $bajominimo ? 'bg-red-50/60' : '' }}"
                            wire:key="reactivo-{{ $reactivo->id }}">
                            <td class="vl-especies-td vl-especies-col--acciones">
                                <div class="flex items-center justify-center gap-0.5">
                                    <a href="{{ route('abm.reactivos.edit', $reactivo->id) }}"
                                       title="Editar reactivo"
                                       aria-label="Editar reactivo"
                                       class="vl-grid-icon-btn text-primary-700 hover:bg-primary-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <x-vl-grid-icon-btn title="Eliminar reactivo"
                                                        variant="danger"
                                                        wire:loading.attr="disabled"
                                                        x-on:click="window.vlSwalConfirmar('¿Eliminar este reactivo? Esta acción no se puede deshacer.', 'Eliminar reactivo', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminar({{ $reactivo->id }}))">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </x-vl-grid-icon-btn>
                                </div>
                            </td>
                            <td class="vl-especies-td font-medium {{ $bajominimo ? 'text-red-700' : '' }}">
                                {{ $reactivo->reactivo }}
                            </td>
                            <td class="vl-especies-td text-right tabular-nums {{ $bajominimo ? 'font-bold text-red-700' : '' }}">
                                {{ number_format($reactivo->cantidad, 0, ',', '.') }}
                            </td>
                            <td class="vl-especies-td text-right tabular-nums text-neutral-600">
                                {{ number_format($reactivo->minAviso, 0, ',', '.') }}
                            </td>
                            <td class="vl-especies-td text-right tabular-nums text-neutral-600">
                                {{ number_format($reactivo->existIdeal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="vl-especies-td py-8 text-center text-neutral-500">
                                No hay reactivos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reactivos->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $reactivos->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>
</div>
