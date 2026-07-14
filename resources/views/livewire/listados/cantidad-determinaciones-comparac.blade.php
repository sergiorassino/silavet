<div
    @class([
        'vl-page',
        'vl-page--wide' => $paso === 'resultado',
    ])
>
    @if ($paso === 'filtros')
        <div class="vl-hero mb-4">
            <div class="vl-hero-inner">
                <p class="vl-eyebrow">Listados estadísticos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Cantidad determinaciones (comparac.)</h1>
                <p class="mt-2 text-sm text-white/80">
                    Seleccione determinaciones y dos períodos a comparar.
                </p>
            </div>
        </div>

        <div class="vl-card overflow-hidden">
            <div class="border-b border-accent-200 bg-neutral-700 px-5 py-3">
                <p class="text-sm font-semibold uppercase tracking-wide text-white">
                    Gráfica de comparación — cantidad de determinaciones realizadas
                    <span class="font-normal normal-case text-white/80">
                        (seleccione determinación/es y dos períodos a comparar)
                    </span>
                </p>
            </div>

            <form wire:submit="generar" class="px-5 py-5 space-y-6">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-cdc-cliente">
                        Cliente
                    </label>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <select wire:model="idClientes"
                                id="vl-cdc-cliente"
                                class="form-input sm:max-w-md"
                                @disabled($clienteBloqueado)>
                            <option value="">Seleccione</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-neutral-500">
                            (Si no usa el filtro por Cliente, el cálculo se hará a nivel de todo el Laboratorio)
                        </p>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-2">
                        Determinaciones a comparar <span class="text-red-600">*</span>
                    </p>
                    <div class="grid gap-3 lg:grid-cols-[1fr_auto_1fr] lg:items-stretch">
                        <div>
                            <label class="sr-only" for="vl-cdc-disponibles">Disponibles</label>
                            <select wire:model="marcadosDisponibles"
                                    id="vl-cdc-disponibles"
                                    multiple
                                    size="12"
                                    class="form-input w-full min-h-[16rem] font-normal">
                                @foreach ($disponibles as $tipo)
                                    <option value="{{ $tipo->idTipodeterminaciones }}">{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-row lg:flex-col items-center justify-center gap-2">
                            <button type="button"
                                    class="btn-secondary px-3 py-1.5 text-sm min-w-[3rem]"
                                    wire:click="moverTodasDerecha"
                                    title="Agregar todas">
                                &gt;&gt;
                            </button>
                            <button type="button"
                                    class="btn-secondary px-3 py-1.5 text-sm min-w-[3rem]"
                                    wire:click="moverDerecha"
                                    title="Agregar seleccionadas">
                                &gt;
                            </button>
                            <button type="button"
                                    class="btn-secondary px-3 py-1.5 text-sm min-w-[3rem]"
                                    wire:click="moverIzquierda"
                                    title="Quitar seleccionadas">
                                &lt;
                            </button>
                            <button type="button"
                                    class="btn-secondary px-3 py-1.5 text-sm min-w-[3rem]"
                                    wire:click="moverTodasIzquierda"
                                    title="Quitar todas">
                                &lt;&lt;
                            </button>
                        </div>

                        <div>
                            <label class="sr-only" for="vl-cdc-seleccionados">Seleccionadas</label>
                            <select wire:model="marcadosSeleccionados"
                                    id="vl-cdc-seleccionados"
                                    multiple
                                    size="12"
                                    class="form-input w-full min-h-[16rem] font-normal">
                                @foreach ($seleccionados as $tipo)
                                    <option value="{{ $tipo->idTipodeterminaciones }}">{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                            @error('idsSeleccionados')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <fieldset class="rounded-lg border border-accent-200 p-4">
                        <legend class="px-1 text-xs font-bold uppercase tracking-wide text-neutral-600">Período 1</legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-cdc-p1-desde">
                                    Período 1 desde <span class="text-red-600">*</span>
                                </label>
                                <input type="date"
                                       wire:model="periodo1Desde"
                                       id="vl-cdc-p1-desde"
                                       class="form-input tabular-nums"
                                       required>
                                @error('periodo1Desde')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-cdc-p1-hasta">
                                    Período 1 hasta <span class="text-red-600">*</span>
                                </label>
                                <input type="date"
                                       wire:model="periodo1Hasta"
                                       id="vl-cdc-p1-hasta"
                                       class="form-input tabular-nums"
                                       required>
                                @error('periodo1Hasta')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="rounded-lg border border-accent-200 p-4">
                        <legend class="px-1 text-xs font-bold uppercase tracking-wide text-neutral-600">Período 2</legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-cdc-p2-desde">
                                    Período 2 desde <span class="text-red-600">*</span>
                                </label>
                                <input type="date"
                                       wire:model="periodo2Desde"
                                       id="vl-cdc-p2-desde"
                                       class="form-input tabular-nums"
                                       required>
                                @error('periodo2Desde')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-neutral-500 mb-1" for="vl-cdc-p2-hasta">
                                    Período 2 hasta <span class="text-red-600">*</span>
                                </label>
                                <input type="date"
                                       wire:model="periodo2Hasta"
                                       id="vl-cdc-p2-hasta"
                                       class="form-input tabular-nums"
                                       required>
                                @error('periodo2Hasta')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                    <button type="submit" class="btn-primary inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Aceptar
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn-secondary inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Volver
                    </a>
                </div>
            </form>
        </div>
    @else
        <div
            x-data="vlCantidadDeterminacionesChart({
                chartPdfUrl: @js($this->chartPdfUrl),
                csrf: @js(csrf_token()),
                query: @js($this->queryExport()),
                payload: @js($payloadGrafico),
            })"
            wire:key="cdc-resultado-{{ md5(json_encode($payloadGrafico)) }}"
            class="space-y-4"
        >
            <div class="vl-card overflow-hidden">
                <div class="border-b border-accent-200 bg-neutral-700 px-5 py-3">
                    <p class="text-sm font-semibold uppercase tracking-wide text-white">
                        Comparativa entre los períodos:
                    </p>
                    <p class="mt-1 text-sm text-white/90">{{ $resumen['periodo1'] }}</p>
                    <p class="text-sm text-white/90">{{ $resumen['periodo2'] }}</p>
                </div>

                <div class="px-4 py-3 flex flex-wrap items-center gap-2 border-b border-accent-100">
                    <div>
                        <label class="sr-only" for="vl-cdc-orden">Ordenar por</label>
                        <select wire:model.live="orden" id="vl-cdc-orden" class="form-input text-sm py-1.5">
                            <option value="nombre">Ordenar por nombre</option>
                            <option value="periodo1_desc">Período 1 (mayor → menor)</option>
                            <option value="periodo1_asc">Período 1 (menor → mayor)</option>
                            <option value="periodo2_desc">Período 2 (mayor → menor)</option>
                            <option value="periodo2_asc">Período 2 (menor → mayor)</option>
                            <option value="diferencia_desc">Diferencia (mayor → menor)</option>
                            <option value="diferencia_asc">Diferencia (menor → mayor)</option>
                        </select>
                    </div>

                    <div class="inline-flex flex-wrap items-center gap-1 rounded-md border border-accent-200 bg-white p-1">
                        @foreach ([
                            'bar' => 'Barras',
                            'line' => 'Líneas',
                            'area' => 'Área',
                            'pie' => 'Torta',
                            'stacked' => 'Apiladas',
                            'horizontalBar' => 'Horizontales',
                        ] as $tipo => $etiqueta)
                            <button type="button"
                                    wire:click="$set('tipoGrafico', '{{ $tipo }}')"
                                    @class([
                                        'px-2 py-1 text-xs rounded',
                                        'bg-primary-700 text-white' => $tipoGrafico === $tipo,
                                        'text-neutral-600 hover:bg-neutral-100' => $tipoGrafico !== $tipo,
                                    ])
                                    title="{{ $etiqueta }}">
                                {{ $etiqueta }}
                            </button>
                        @endforeach
                    </div>

                    <button type="button"
                            class="btn-secondary text-sm py-1.5"
                            wire:click="$toggle('mostrarResumen')">
                        {{ $mostrarResumen ? 'Ocultar resumen' : 'Resumen' }}
                    </button>

                    <div class="relative ml-auto" @click.outside="exportOpen = false">
                        <button type="button"
                                class="btn-secondary text-sm py-1.5 inline-flex items-center gap-1"
                                @click="exportOpen = !exportOpen">
                            Exportar
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="exportOpen"
                             x-cloak
                             class="absolute right-0 z-20 mt-1 w-52 rounded-md border border-accent-200 bg-white shadow-lg py-1">
                            <a href="{{ $this->pdfUrl }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="block px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50"
                               @click="exportOpen = false">
                                PDF (tabla de datos)
                            </a>
                            <a href="{{ $this->excelUrl }}"
                               class="block px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50"
                               @click="exportOpen = false">
                                Excel
                            </a>
                            <button type="button"
                                    class="w-full text-left px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-50"
                                    @click="exportOpen = false; exportarGraficoPdf()">
                                PDF (gráfico)
                            </button>
                        </div>
                    </div>

                    <button type="button" class="btn-secondary text-sm py-1.5" wire:click="volverFiltros">
                        Volver
                    </button>
                </div>

                @if ($mostrarResumen)
                    <div class="px-5 py-3 grid gap-3 sm:grid-cols-3 border-b border-accent-100 bg-sky-50/60 text-sm">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-neutral-500">Total período 1</p>
                            <p class="font-semibold tabular-nums text-neutral-800">{{ number_format($resumen['total1'], 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-neutral-500">Total período 2</p>
                            <p class="font-semibold tabular-nums text-neutral-800">{{ number_format($resumen['total2'], 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-neutral-500">Diferencia (P2 − P1)</p>
                            <p class="font-semibold tabular-nums text-neutral-800">
                                {{ ($resumen['totalDiferencia'] > 0 ? '+' : '').number_format($resumen['totalDiferencia'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                @endif

                <div class="px-4 py-4">
                    @if ($filas->isEmpty())
                        <p class="text-sm text-neutral-500 text-center py-10">
                            No hay datos para los filtros seleccionados.
                        </p>
                    @else
                        <div class="relative h-[28rem] w-full" wire:ignore>
                            <canvas x-ref="canvas" class="max-h-full w-full" aria-label="Gráfico comparativo de determinaciones"></canvas>
                        </div>
                    @endif
                </div>

                @if ($filas->isNotEmpty())
                    <div class="overflow-x-auto border-t border-accent-100">
                        <table class="min-w-full text-sm">
                            <thead class="bg-accent-50 text-left text-xs uppercase tracking-wide text-neutral-600">
                                <tr>
                                    <th class="px-4 py-2 font-semibold">Determinación</th>
                                    <th class="px-4 py-2 font-semibold text-right">Período 1</th>
                                    <th class="px-4 py-2 font-semibold text-right">Período 2</th>
                                    <th class="px-4 py-2 font-semibold text-right">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($filas as $fila)
                                    <tr class="border-t border-accent-100">
                                        <td class="px-4 py-2">{{ $fila->nombre }}</td>
                                        <td class="px-4 py-2 text-right tabular-nums">{{ number_format($fila->cantidad1, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-right tabular-nums">{{ number_format($fila->cantidad2, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-right tabular-nums">
                                            {{ ($fila->diferencia > 0 ? '+' : '').number_format($fila->diferencia, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-accent-200 bg-neutral-50 font-semibold">
                                    <td class="px-4 py-2">Totales</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ number_format($resumen['total1'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ number_format($resumen['total2'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">
                                        {{ ($resumen['totalDiferencia'] > 0 ? '+' : '').number_format($resumen['totalDiferencia'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
