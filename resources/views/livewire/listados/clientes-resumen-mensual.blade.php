<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Listados estadísticos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Clientes resumen mensual</h1>
                <p class="mt-2 text-sm text-white/80">
                    Protocolos del período con desglose de IVA, pagos y determinaciones, agrupados por cliente veterinario.
                    Período: <span class="font-semibold">{{ $periodoTexto }}</span>
                </p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-card overflow-hidden mb-4">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-4 flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-crm-desde">Fecha desde</label>
                <input type="date"
                       wire:model.live="fechaDesde"
                       id="vl-crm-desde"
                       class="form-input tabular-nums">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-crm-hasta">Fecha hasta</label>
                <input type="date"
                       wire:model.live="fechaHasta"
                       id="vl-crm-hasta"
                       class="form-input tabular-nums">
            </div>
            <div class="min-w-[12rem] flex-1 max-w-sm">
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-crm-cliente">Cliente</label>
                <select wire:model.live="idClientes"
                        id="vl-crm-cliente"
                        class="form-input"
                        @disabled($clienteBloqueado)>
                    <option value="">-- Todos los clientes --</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-2 pb-0.5">
                <button type="button" class="btn-primary text-sm" wire:click="filtrar">
                    Filtrar
                </button>
                <a href="{{ $this->pdfUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="vl-crm-btn vl-crm-btn--pdf">
                    Exportar PDF
                </a>
                <a href="{{ $this->excelUrl }}"
                   class="vl-crm-btn vl-crm-btn--excel">
                    Exportar Excel
                </a>
                <button type="button" class="btn-secondary text-sm" wire:click="limpiarFiltros">
                    Limpiar
                </button>
            </div>
        </div>
        <div class="px-5 py-2 text-xs text-neutral-600">
            Período: <strong>{{ $periodoTexto }}</strong>
            &nbsp;|&nbsp; Cliente: <strong>{{ $infoCliente['nombre'] }}</strong>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="vl-crm-grid min-w-full">
                <colgroup>
                    <col class="vl-crm-col-fecha">
                    <col class="vl-crm-col-cliente">
                    <col class="vl-crm-col-protocolo">
                    <col class="vl-crm-col-paciente">
                    <col class="vl-crm-col-num">
                    <col class="vl-crm-col-num">
                    <col class="vl-crm-col-num">
                    <col class="vl-crm-col-num">
                    <col class="vl-crm-col-medio">
                    <col class="vl-crm-col-det">
                </colgroup>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Protocolo</th>
                        <th>Paciente</th>
                        <th class="num vl-crm-col-sin">Neto sin IVA</th>
                        <th class="num vl-crm-col-sin">IVA</th>
                        <th class="num vl-crm-col-sin">Precio con IVA</th>
                        <th class="num">Pagado</th>
                        <th>Medio pago</th>
                        <th class="th-det">Determinaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bloques as $bloque)
                        @if ($bloque['tipo'] === 'grupo')
                            <tr class="vl-crm-grupo">
                                <td colspan="10">{{ $bloque['cliente'] }}</td>
                            </tr>
                        @elseif ($bloque['tipo'] === 'subtotal')
                            <tr class="vl-crm-subtotal">
                                <td colspan="4" class="vl-crm-total-label">
                                    Subtotal {{ $bloque['cliente'] }} ({{ $bloque['cantidad'] }} pac.)
                                </td>
                                <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $bloque['sum_sin_iva']) }}</td>
                                <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $bloque['sum_iva']) }}</td>
                                <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $bloque['sum_con_iva']) }}</td>
                                <td class="num">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $bloque['sum_pagado']) }}</td>
                                <td></td>
                                <td></td>
                            </tr>
                        @else
                            @php $fila = $bloque['fila']; @endphp
                            <tr class="vl-crm-fila" wire:key="crm-{{ $fila->idPacientes }}">
                                <td class="c-fecha">
                                    {{ $fila->fechhoy !== '' ? \Carbon\Carbon::parse($fila->fechhoy)->format('d/m/Y') : '—' }}
                                </td>
                                <td class="c-cliente">{{ $fila->cliente }}</td>
                                <td class="c-protocolo">{{ $fila->nombreProtocolo !== '' ? $fila->nombreProtocolo : '—' }}</td>
                                <td class="c-paciente">{{ $fila->nombre !== '' ? $fila->nombre : '—' }}</td>
                                <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $fila->sin_iva) }}</td>
                                <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $fila->iva) }}</td>
                                <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $fila->con_iva) }}</td>
                                <td class="num">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $fila->pagado) }}</td>
                                <td class="c-medio">{{ $fila->mediodepago }}</td>
                                <td class="c-det">
                                    @if ($fila->determinaciones === [])
                                        <em class="vl-crm-sin-det">Sin determinaciones registradas.</em>
                                    @else
                                        <table class="vl-crm-mini">
                                            <tbody>
                                                @foreach ($fila->determinaciones as $det)
                                                    <tr>
                                                        <td class="c-det-nom">{{ $det->nombre }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" class="vl-crm-vacio">{{ $mensajeVacio }}</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($registros->total() > 0)
                    <tfoot>
                        @if ($registros->hasPages())
                            <tr class="vl-crm-pagina">
                                <td colspan="4" class="vl-crm-total-label">
                                    Totales de la página ({{ $totalesPagina['cantidad'] }} pac.)
                                </td>
                                <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $totalesPagina['sum_sin_iva']) }}</td>
                                <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $totalesPagina['sum_iva']) }}</td>
                                <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $totalesPagina['sum_con_iva']) }}</td>
                                <td class="num">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $totalesPagina['sum_pagado']) }}</td>
                                <td></td>
                                <td></td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="4" class="vl-crm-total-label">
                                Total general ({{ $totalesGeneral['cantidad'] }} pac.)
                            </td>
                            <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $totalesGeneral['sum_sin_iva']) }}</td>
                            <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $totalesGeneral['sum_iva']) }}</td>
                            <td class="num vl-crm-col-sin">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $totalesGeneral['sum_con_iva']) }}</td>
                            <td class="num">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $totalesGeneral['sum_pagado']) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if ($infoCliente['mostrar'] && $registros->total() > 0)
            <div class="vl-crm-descuento">
                <div class="vl-crm-descuento-titulo">{{ $infoCliente['etiqueta'] }}</div>
                <div class="vl-crm-descuento-items">
                    <div class="vl-crm-descuento-item">
                        <span class="vl-crm-descuento-lbl">Neto sin IVA</span>
                        <span class="vl-crm-descuento-val">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $totalesGeneral['sum_cd_sin_iva']) }}</span>
                    </div>
                    <div class="vl-crm-descuento-item">
                        <span class="vl-crm-descuento-lbl">IVA</span>
                        <span class="vl-crm-descuento-val">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $totalesGeneral['sum_cd_iva']) }}</span>
                    </div>
                    <div class="vl-crm-descuento-item">
                        <span class="vl-crm-descuento-lbl">Precio con IVA</span>
                        <span class="vl-crm-descuento-val">{{ \App\Support\Listados\ClientesResumenMensualConsulta::formatearMoneda((float) $totalesGeneral['sum_cd_con_iva']) }}</span>
                    </div>
                </div>
            </div>
        @endif

        @if ($registros->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $registros->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>
</div>
