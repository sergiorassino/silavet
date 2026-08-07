<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Clientes · Cuenta corriente</p>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $cliente->nombre }}</h1>
                <p class="mt-2 text-sm text-white/80">
                    Movimientos en cuenta corriente.
                    Saldo total al día de hoy:
                    <span class="font-semibold tabular-nums">{{ \App\Support\CuentaCorriente\CuentaCorrienteMovimientosConsulta::formatearMoneda($saldoHoy) }}</span>
                </p>
            </x-vl-hero-heading>
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
                {{ \App\Support\CuentaCorriente\CuentaCorrienteMovimientosConsulta::etiquetaPeriodo($fechaDesde, $fechaHasta) }}
            </p>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="vl-pacientes-grid min-w-full text-xs">
                <thead class="bg-accent-50/80">
                    <tr>
                        <th class="vl-pacientes-th vl-pacientes-th--num">#</th>
                        <th class="vl-pacientes-th">Nombre</th>
                        <th class="vl-pacientes-th">Id Cuentas</th>
                        <th class="vl-pacientes-th whitespace-nowrap">Fechhora</th>
                        <th class="vl-pacientes-th vl-pacientes-th--num">Monto</th>
                        <th class="vl-pacientes-th">Obs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-accent-100">
                    @forelse ($filas as $fila)
                        <tr class="vl-pacientes-row {{ $fila->esNegativo ? 'vl-cc-mov-row--negativo' : 'hover:bg-accent-50/40' }}">
                            <td class="vl-pacientes-td vl-pacientes-td--num">{{ $loop->iteration }}</td>
                            <td class="vl-pacientes-td">{{ $fila->etiquetaPaciente ?: '—' }}</td>
                            <td class="vl-pacientes-td whitespace-nowrap">{{ $fila->cuentaLabel ?: $fila->idCuentas }}</td>
                            <td class="vl-pacientes-td whitespace-nowrap tabular-nums">
                                {{ $fila->fechhora !== '' ? \Carbon\Carbon::parse($fila->fechhora)->format('d/m/Y H:i:s') : '—' }}
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap tabular-nums font-semibold {{ $fila->esNegativo ? 'text-green-700' : '' }}">
                                {{ \App\Support\CuentaCorriente\CuentaCorrienteMovimientosConsulta::formatearMoneda($fila->monto) }}
                            </td>
                            <td class="vl-pacientes-td text-neutral-600">{{ $fila->obs ?: '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="vl-pacientes-td text-center text-neutral-500 py-8">
                                No hay movimientos en el período seleccionado.
                            </td>
                        </tr>
                    @endforelse
                    @if ($saldoAnterior !== null)
                        <tr class="bg-accent-50/60 font-semibold">
                            <td colspan="4" class="vl-pacientes-td text-right">
                                Saldo anterior al {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num whitespace-nowrap tabular-nums">
                                {{ \App\Support\CuentaCorriente\CuentaCorrienteMovimientosConsulta::formatearMoneda($saldoAnterior) }}
                            </td>
                            <td class="vl-pacientes-td"></td>
                        </tr>
                    @endif
                </tbody>
                @if ($filas->isNotEmpty() || $saldoAnterior !== null)
                    <tfoot class="bg-accent-50/60 border-t border-accent-200">
                        <tr>
                            <td colspan="4" class="vl-pacientes-td text-right font-semibold">
                                Total período:
                            </td>
                            <td class="vl-pacientes-td vl-pacientes-td--num font-semibold whitespace-nowrap tabular-nums">
                                {{ \App\Support\CuentaCorriente\CuentaCorrienteMovimientosConsulta::formatearMoneda((float) $resumen['total_monto']) }}
                            </td>
                            <td class="vl-pacientes-td"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
