<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="vl-eyebrow">ABM</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Clientes</h1>
                <p class="mt-2 text-sm text-white/80">Veterinarias y clínicas del laboratorio.</p>
            </div>
            <a href="{{ route('abm.clientes.create') }}" class="btn-primary shrink-0 bg-white text-primary-700 hover:bg-accent-50">Nuevo cliente</a>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por nombre, email o CUIT…"
                   class="form-input max-w-md">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="table-header">Nombre</th>
                        <th class="table-header">Teléfono</th>
                        <th class="table-header">Email</th>
                        <th class="table-header">CUIT</th>
                        <th class="table-header text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @forelse ($clientes as $cliente)
                        <tr class="hover:bg-accent-50/40">
                            <td class="table-cell font-medium">{{ $cliente->nombre }}</td>
                            <td class="table-cell">{{ $cliente->telefono1 ?: '—' }}</td>
                            <td class="table-cell">{{ $cliente->email ?: '—' }}</td>
                            <td class="table-cell">{{ $cliente->cuit ?: '—' }}</td>
                            <td class="table-cell text-right">
                                <a href="{{ route('abm.clientes.edit', $cliente->idClientes) }}"
                                   class="text-primary-700 hover:text-primary-900 font-semibold">
                                    Editar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-cell text-center text-neutral-500 py-8">
                                No hay clientes registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($clientes->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $clientes->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>
</div>
