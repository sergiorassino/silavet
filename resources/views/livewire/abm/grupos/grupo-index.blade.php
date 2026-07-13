<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="vl-eyebrow">ABM</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Gestión de Grupos</h1>
                <p class="mt-2 text-sm text-white/80">Agrupación de ítems en informes de laboratorio.</p>
            </div>
            <a href="{{ route('admin.grupos.create') }}" class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">Nuevo grupo</a>
        </div>
    </div>

    <div class="vl-card mx-auto max-w-2xl overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-3 py-2">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por nombre u orden…"
                   class="form-input max-w-xs py-1.5 text-sm">
        </div>

        <div class="flex justify-center overflow-x-auto px-2 py-1">
            <table class="vl-grupos-grid text-sm">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-grupos-th vl-grupos-col--orden">Orden</th>
                        <th class="vl-grupos-th vl-grupos-col--nombre">Nombre del grupo</th>
                        <th class="vl-grupos-th vl-grupos-col--acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @forelse ($grupos as $grupo)
                        <tr class="vl-grupos-row" wire:key="grupo-{{ $grupo->idGrupos }}">
                            <td class="vl-grupos-td vl-grupos-col--orden tabular-nums">{{ $grupo->orden ?? '—' }}</td>
                            <td class="vl-grupos-td vl-grupos-col--nombre font-medium">{{ $grupo->nombreGrupo }}</td>
                            <td class="vl-grupos-td vl-grupos-col--acciones">
                                <div class="flex items-center justify-center gap-0.5">
                                    <a href="{{ route('admin.grupos.edit', $grupo->idGrupos) }}"
                                       title="Editar grupo"
                                       aria-label="Editar grupo"
                                       class="vl-grid-icon-btn text-primary-700 hover:bg-primary-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <x-vl-grid-icon-btn title="Eliminar grupo"
                                                        variant="danger"
                                                        wire:loading.attr="disabled"
                                                        x-on:click="window.vlSwalConfirmar('¿Eliminar este grupo? Esta acción no se puede deshacer.', 'Eliminar grupo', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminar({{ $grupo->idGrupos }}))">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </x-vl-grid-icon-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="vl-grupos-td text-center text-neutral-500 py-4">
                                No hay grupos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($grupos->hasPages())
            <div class="vl-matriz-list-footer border-t border-accent-200 px-3 py-2">
                {{ $grupos->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>
</div>
