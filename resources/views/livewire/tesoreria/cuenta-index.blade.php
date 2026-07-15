<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Tesorería</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Gestión de Cuentas Contables</h1>
                <p class="mt-2 text-sm text-white/80">Plan de cuentas (nivel 1).</p>
            </x-vl-hero-heading>
            <a href="{{ route('tesoreria.cuentas.create') }}" class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">Nueva cuenta</a>
        </div>
    </div>

    <div class="vl-card mx-auto max-w-2xl overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-3 py-2">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por nombre…"
                   class="form-input max-w-xs py-1.5 text-sm">
        </div>

        <div class="flex justify-center overflow-x-auto px-2 py-1">
            <table class="vl-grid-pocos-campos vl-grid-angosta-wrap text-sm">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-neutral-700">Nombre</th>
                        <th class="px-3 py-2 text-center font-semibold text-neutral-700 w-24">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @forelse ($cuentas as $cuenta)
                        <tr wire:key="cuenta-{{ $cuenta->id }}">
                            <td class="px-3 py-2 font-medium">{{ $cuenta->nombreCuenta }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-center gap-0.5">
                                    <a href="{{ route('tesoreria.cuentas.edit', $cuenta->id) }}"
                                       title="Editar cuenta"
                                       aria-label="Editar cuenta"
                                       class="vl-grid-icon-btn text-primary-700 hover:bg-primary-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <x-vl-grid-icon-btn title="Eliminar cuenta"
                                                        variant="danger"
                                                        wire:loading.attr="disabled"
                                                        x-on:click="window.vlSwalConfirmar('¿Eliminar esta cuenta? Esta acción no se puede deshacer.', 'Eliminar cuenta', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminar({{ $cuenta->id }}))">
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
                            <td colspan="2" class="px-3 py-4 text-center text-neutral-500">
                                No hay cuentas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cuentas->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $cuentas->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>
</div>
