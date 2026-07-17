@php
    use App\Support\Tesoreria\SaldosPorDiaConsulta;
@endphp

<div class="vl-page vl-page--wide">
    <div class="vl-hero mb-3">
        <div class="vl-hero-inner flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Tesorería</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Saldos por Día</h1>
                <p class="mt-1 text-sm text-white/80">Saldos inicial y final por cuenta, con detalle de movimientos.</p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-card overflow-hidden">
        <div class="vl-toolbar flex flex-wrap items-end gap-3 border-b border-accent-200 px-3 py-2">
            <div>
                <label class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-neutral-500" for="spdDesde">Desde</label>
                <input id="spdDesde"
                       type="date"
                       wire:model.live="fechaDesde"
                       class="form-input text-xs">
            </div>
            <div>
                <label class="mb-0.5 block text-[10px] font-bold uppercase tracking-wide text-neutral-500" for="spdHasta">Hasta</label>
                <input id="spdHasta"
                       type="date"
                       wire:model.live="fechaHasta"
                       class="form-input text-xs">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="vl-saldos-dia-grid">
                <thead>
                    <tr>
                        <th class="vl-saldos-dia-th vl-saldos-dia-th--icon"></th>
                        <th class="vl-saldos-dia-th whitespace-nowrap">Fecha</th>
                        @foreach ($cuentas as $idx => $cuenta)
                            @php $tone = $idx % 2 === 0 ? 'tone-a' : 'tone-b'; @endphp
                            <th class="vl-saldos-dia-th vl-saldos-dia-th--num vl-saldos-dia-th--{{ $tone }}"
                                title="{{ $cuenta['nombre'] }} (Inicial)">
                                {{ $cuenta['abrev'] }} (Inicial)
                            </th>
                            <th class="vl-saldos-dia-th vl-saldos-dia-th--num vl-saldos-dia-th--{{ $tone }}"
                                title="{{ $cuenta['nombre'] }} (Final)">
                                {{ $cuenta['abrev'] }} (Final)
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dias as $dia)
                        @php
                            $fecha = $dia['fecha'];
                            $expandido = $diaExpandido === $fecha;
                            $fechaFmt = \Carbon\Carbon::parse($fecha)->format('d/m/Y');
                        @endphp
                        <tr class="vl-saldos-dia-row {{ $expandido ? 'vl-saldos-dia-row--open' : '' }}">
                            <td class="vl-saldos-dia-td vl-saldos-dia-td--icon">
                                <button type="button"
                                        wire:click="toggleDia('{{ $fecha }}')"
                                        class="vl-saldos-dia-toggle"
                                        title="{{ $expandido ? 'Contraer' : 'Expandir' }}"
                                        aria-expanded="{{ $expandido ? 'true' : 'false' }}">
                                    <svg class="h-3 w-3 transition-transform {{ $expandido ? 'rotate-90' : '' }}"
                                         fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M6.5 4.5l7 5.5-7 5.5V4.5z"/>
                                    </svg>
                                </button>
                            </td>
                            <td class="vl-saldos-dia-td font-semibold whitespace-nowrap">{{ $fechaFmt }}</td>
                            @foreach ($dia['cuentas'] as $idx => $saldo)
                                @php $tone = $idx % 2 === 0 ? 'tone-a' : 'tone-b'; @endphp
                                <td class="vl-saldos-dia-td vl-saldos-dia-td--num vl-saldos-dia-td--{{ $tone }}">
                                    {{ SaldosPorDiaConsulta::formatearMonto($saldo['inicial']) }}
                                </td>
                                <td class="vl-saldos-dia-td vl-saldos-dia-td--num vl-saldos-dia-td--{{ $tone }}">
                                    {{ SaldosPorDiaConsulta::formatearMonto($saldo['final']) }}
                                </td>
                            @endforeach
                        </tr>

                        @if ($expandido)
                            @foreach ($dia['cuentas'] as $saldo)
                                @php
                                    $claveCuenta = $fecha.':'.$saldo['id'];
                                    $cuentaAbierta = $cuentaExpandida === $claveCuenta;
                                @endphp
                                <tr class="vl-saldos-dia-row vl-saldos-dia-row--cuenta">
                                    <td class="vl-saldos-dia-td vl-saldos-dia-td--icon">
                                        <button type="button"
                                                wire:click="toggleCuenta('{{ $fecha }}', {{ $saldo['id'] }})"
                                                class="vl-saldos-dia-toggle-cuenta"
                                                title="{{ $cuentaAbierta ? 'Contraer movimientos' : 'Ver movimientos' }}"
                                                aria-expanded="{{ $cuentaAbierta ? 'true' : 'false' }}">
                                            {{ $cuentaAbierta ? '−' : '+' }}
                                        </button>
                                    </td>
                                    <td class="vl-saldos-dia-td" colspan="{{ 1 + (count($cuentas) * 2) }}">
                                        <span class="font-medium">{{ $saldo['nombre'] }}</span>
                                        <span class="ml-2 tabular-nums {{ $saldo['delta'] < 0 ? 'text-red-700' : '' }}">
                                            {{ SaldosPorDiaConsulta::formatearMonto($saldo['delta']) }}
                                        </span>
                                    </td>
                                </tr>

                                @if ($cuentaAbierta)
                                    <tr class="vl-saldos-dia-row vl-saldos-dia-row--detalle">
                                        <td class="vl-saldos-dia-td p-0" colspan="{{ 2 + (count($cuentas) * 2) }}">
                                            <div class="overflow-x-auto bg-white px-2 py-2">
                                                <table class="vl-saldos-dia-detalle">
                                                    <thead>
                                                        <tr>
                                                            <th class="vl-saldos-dia-detalle-th">Fechhora</th>
                                                            <th class="vl-saldos-dia-detalle-th">Tipo</th>
                                                            <th class="vl-saldos-dia-detalle-th">Cuenta</th>
                                                            <th class="vl-saldos-dia-detalle-th">Concepto</th>
                                                            <th class="vl-saldos-dia-detalle-th">Cliente</th>
                                                            <th class="vl-saldos-dia-detalle-th">Paciente</th>
                                                            <th class="vl-saldos-dia-detalle-th">Proveedores</th>
                                                            <th class="vl-saldos-dia-detalle-th vl-saldos-dia-detalle-th--num">Monto</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($movimientosDetalle as $mov)
                                                            <tr>
                                                                <td class="vl-saldos-dia-detalle-td whitespace-nowrap">
                                                                    {{ $mov->fechhora?->format('d/m/Y H:i:s') ?? '—' }}
                                                                </td>
                                                                <td class="vl-saldos-dia-detalle-td">
                                                                    {{ $mov->tipoMovimiento?->tipoMovimiento ?: '—' }}
                                                                </td>
                                                                <td class="vl-saldos-dia-detalle-td">
                                                                    {{ $mov->cuenta?->nombreMedioPago ?: '—' }}
                                                                </td>
                                                                <td class="vl-saldos-dia-detalle-td">
                                                                    {{ $mov->concepto?->concepto ?: '—' }}
                                                                </td>
                                                                <td class="vl-saldos-dia-detalle-td">
                                                                    {{ ((int) ($mov->idClientes ?? 0) > 0) ? ($mov->cliente?->nombre ?: '—') : '' }}
                                                                </td>
                                                                <td class="vl-saldos-dia-detalle-td whitespace-nowrap">
                                                                    {{ $mov->etiquetaPaciente() }}
                                                                </td>
                                                                <td class="vl-saldos-dia-detalle-td">
                                                                    {{ ((int) ($mov->idProveedores ?? 0) > 0) ? ($mov->proveedor?->proveedor ?: '') : '' }}
                                                                </td>
                                                                <td class="vl-saldos-dia-detalle-td vl-saldos-dia-detalle-td--num whitespace-nowrap">
                                                                    {{ $mov->montoFormateado() }}
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="8" class="vl-saldos-dia-detalle-td py-3 text-center text-neutral-500">
                                                                    Sin movimientos en esta cuenta para el día.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                    @if ($movimientosDetalle->isNotEmpty())
                                                        <tfoot>
                                                            <tr class="vl-saldos-dia-detalle-suma">
                                                                <td class="vl-saldos-dia-detalle-td font-bold" colspan="7">Suma</td>
                                                                <td class="vl-saldos-dia-detalle-td vl-saldos-dia-detalle-td--num font-bold whitespace-nowrap">
                                                                    {{ SaldosPorDiaConsulta::formatearMonto($sumaDetalle) }}
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    @endif
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ 2 + max(1, count($cuentas) * 2) }}"
                                class="vl-saldos-dia-td py-6 text-center text-neutral-500">
                                No hay días con movimientos en el rango seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($dias->hasPages())
            <div class="vl-matriz-list-footer px-3 py-1.5 sm:px-4">
                {{ $dias->links('vendor.pagination.vl-compact') }}
            </div>
        @endif
    </div>
</div>
