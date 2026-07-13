<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="vl-eyebrow">Clientes · Cuenta corriente</p>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $cliente->nombre }}</h1>
                <p class="mt-2 text-sm text-white/80">
                    Protocolos históricos del cliente.
                    Saldo total al día de hoy:
                    <span class="font-semibold tabular-nums">{{ \App\Support\CuentaCorriente\CuentaCorrienteConsulta::formatearMoneda($saldoHoy) }}</span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ route('clientes.cuenta-corriente.index') }}"
                   class="btn-secondary bg-white/10 text-white border-white/30 hover:bg-white/20">
                    Volver al listado
                </a>
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

    <div class="vl-card overflow-hidden mb-4">
        <div class="vl-toolbar border-b border-accent-200 px-5 py-4 flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1">Desde</label>
                <input type="date"
                       wire:model.live="fechaDesde"
                       class="form-input tabular-nums">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1">Hasta</label>
                <input type="date"
                       wire:model.live="fechaHasta"
                       class="form-input tabular-nums">
            </div>
            <p class="text-sm text-neutral-600 pb-1">
                Período seleccionado:
                {{ \App\Support\CuentaCorriente\CuentaCorrienteConsulta::etiquetaPeriodo($fechaDesde, $fechaHasta) }}
            </p>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="vl-pacientes-grid min-w-full text-xs">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-pacientes-th vl-pacientes-th--num">#</th>
                        <th class="vl-pacientes-th">Id Pacientes</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Id Clientes</th>
                        <th class="vl-pacientes-th">Id Especies</th>
                        <th class="vl-pacientes-th">Id Razas</th>
                        <th class="vl-pacientes-th">Fechhoy</th>
                        <th class="vl-pacientes-th">Nombre Protocolo</th>
                        <th class="vl-pacientes-th">Nombre</th>
                        <th class="vl-pacientes-th">Propietario</th>
                        <th class="vl-pacientes-th">Estado</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Precio</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Pagado</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @forelse ($filas as $fila)
                        <tr class="vl-pacientes-row {{ $fila->esPagoGlobal ? 'vl-pacientes-row--pago-global' : 'hover:bg-accent-50/40' }}">
                            <td class="vl-pacientes-td vl-pacientes-td--num">{{ $loop->iteration }}</td>
                            <td class="vl-pacientes-td">{{ $fila->nombre ?: '—' }}</td>
                            <td class="vl-pacientes-td vl-pacientes-td--num">{{ $fila->idClientes ?: '—' }}</td>
                            <td class="vl-pacientes-td">{{ $fila->especie ?: '—' }}</td>
                            <td class="vl-pacientes-td">{{ $fila->raza ?: '—' }}</td>
                            <td class="vl-pacientes-td whitespace-nowrap tabular-nums">
                                {{ $fila->fechhoy !== '' ? \Carbon\Carbon::parse($fila->fechhoy)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="vl-pacientes-td font-semibold whitespace-nowrap">{{ $fila->nombreProtocolo ?: '—' }}</td>
                            <td class="vl-pacientes-td">
                                @if ($fila->esPagoGlobal)
                                    <span class="vl-pacientes-pago-global-badge">Pago global</span>
                                @else
                                    {{ $fila->nombre ?: '—' }}
                                @endif
                            </td>
                            <td class="vl-pacientes-td">{{ $fila->propietario ?: '—' }}</td>
                            <td class="vl-pacientes-td whitespace-nowrap">{{ $fila->estado ?: '—' }}</td>
                            <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">
                                {{ \App\Support\CuentaCorriente\CuentaCorrienteConsulta::formatearMoneda((float) $fila->precio) }}
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap">
                                {{ \App\Support\CuentaCorriente\CuentaCorrienteConsulta::formatearMoneda((float) $fila->pagado) }}
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap font-semibold">
                                {{ \App\Support\CuentaCorriente\CuentaCorrienteConsulta::formatearMoneda((float) $fila->saldo) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="vl-pacientes-td text-center text-neutral-500 py-8">
                                No hay protocolos en el período seleccionado.
                            </td>
                        </tr>
                    @endforelse
                    @if ($saldoAnterior !== null)
                        <tr class="bg-accent-50/60 font-semibold">
                            <td colspan="12" class="vl-pacientes-td text-right">
                                Saldo anterior al {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap tabular-nums">
                                {{ \App\Support\CuentaCorriente\CuentaCorrienteConsulta::formatearMoneda($saldoAnterior) }}
                            </td>
                        </tr>
                    @endif
                </tbody>
                @if ($filas->isNotEmpty() || $saldoAnterior !== null)
                    <tfoot class="bg-accent-50/60 border-t border-accent-200">
                        <tr>
                            <td colspan="10" class="vl-pacientes-td text-right font-semibold">
                                Total:
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num font-semibold whitespace-nowrap">
                                {{ \App\Support\CuentaCorriente\CuentaCorrienteConsulta::formatearMoneda((float) $resumen['total_precio']) }}
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num font-semibold whitespace-nowrap">
                                {{ \App\Support\CuentaCorriente\CuentaCorrienteConsulta::formatearMoneda((float) $resumen['total_pagado']) }}
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
