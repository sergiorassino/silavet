<div class="vl-page">
    <div class="vl-prot-det-header mb-4">
        <div class="flex flex-col gap-0.5 px-5 py-3 text-sm">
            <span class="font-semibold">{{ $detalle['cliente'] }}</span>
            <span>{{ $detalle['paciente'] }}</span>
            <span class="text-white/80">{{ $detalle['protocolo'] }}</span>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 items-center gap-2 min-w-0">
                <label for="busqueda-rapida-det-cli" class="text-xs font-semibold text-neutral-600 whitespace-nowrap">Búsqueda Rápida:</label>
                <input id="busqueda-rapida-det-cli"
                       wire:model.live.debounce.300ms="busquedaRapida"
                       type="search"
                       placeholder="Filtrar determinaciones…"
                       class="form-input max-w-md flex-1">
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ $urlPdf }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn-secondary text-sm">
                    PDF
                </a>
                <a href="{{ $urlVolver }}"
                   class="btn-secondary text-sm">
                    Volver
                </a>
            </div>
        </div>

        <div class="vl-prot-det-wrap">
            <table class="vl-determinaciones-grid vl-prot-det-grid text-sm">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-determinaciones-th vl-prot-det-col--tipo">Determinaciones Solicitadas</th>
                        <th class="vl-determinaciones-th vl-prot-det-col--neto" title="Precio de lista (neto)">Precio</th>
                        <th class="vl-determinaciones-th vl-prot-det-col--descuento" title="Importe de descuento">Descuento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($detalle['filas'] as $i => $fila)
                        <tr class="vl-determinaciones-row" wire:key="det-cli-{{ $i }}-{{ $fila['nombre'] }}">
                            <td class="vl-determinaciones-td vl-prot-det-col--tipo">{{ $fila['nombre'] }}</td>
                            <td class="vl-determinaciones-td vl-prot-det-col--neto tabular-nums">{{ $fila['neto_fmt'] }}</td>
                            <td class="vl-determinaciones-td vl-prot-det-col--descuento tabular-nums">{{ $fila['descuento_fmt'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="vl-determinaciones-td text-center text-neutral-500 py-10">
                                No hay determinaciones solicitadas en este protocolo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t border-accent-200 bg-accent-50/50">
                    <tr>
                        <td class="vl-determinaciones-td vl-prot-det-footer-label text-xs font-semibold text-neutral-600 py-2">
                            Total Acumulado -
                        </td>
                        <td class="vl-determinaciones-td vl-prot-det-footer-total text-xs font-bold text-neutral-900 tabular-nums py-2">
                            {{ $detalle['total_neto_fmt'] }}
                        </td>
                        <td class="vl-determinaciones-td vl-prot-det-footer-total text-xs font-bold text-neutral-900 tabular-nums py-2">
                            {{ $detalle['total_descuento_fmt'] }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="border-t border-accent-200 px-5 py-3 text-sm font-semibold text-neutral-800">
            Total con Descuentos: {{ $detalle['total_con_descuento_fmt'] }}
        </p>
    </div>
</div>
