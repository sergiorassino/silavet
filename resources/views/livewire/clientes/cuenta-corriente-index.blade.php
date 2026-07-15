<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Clientes</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Cuenta corriente</h1>
                <p class="mt-2 text-sm text-white/80">Saldo pendiente de cada cliente al día de hoy.</p>
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

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3">
            <input wire:model.live.debounce.300ms="busqueda"
                   type="search"
                   placeholder="Buscar por nombre, dirección o teléfono…"
                   class="form-input max-w-md">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="table-header">Nombre</th>
                        <th class="table-header">Dirección</th>
                        <th class="table-header">Teléfono</th>
                        <th class="table-header text-right">Saldo</th>
                        <th class="table-header text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @forelse ($clientes as $cliente)
                        <tr class="hover:bg-accent-50/40">
                            <td class="table-cell font-medium">{{ $cliente->nombre }}</td>
                            <td class="table-cell">{{ $cliente->direccion ?: '—' }}</td>
                            <td class="table-cell">{{ $cliente->telefono1 ?: ($cliente->telefono2 ?: '—') }}</td>
                            <td class="table-cell text-right tabular-nums font-semibold {{ (float) $cliente->saldo_total > 0 ? 'text-red-700' : 'text-neutral-700' }}">
                                {{ \App\Support\CuentaCorriente\CuentaCorrienteConsulta::formatearMoneda((float) $cliente->saldo_total) }}
                            </td>
                            <td class="table-cell text-right">
                                <a href="{{ route('clientes.cuenta-corriente.detalle', $cliente->idClientes) }}"
                                   class="text-primary-700 hover:text-primary-900 font-semibold">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-cell text-center text-neutral-500 py-8">
                                No hay clientes para mostrar.
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
