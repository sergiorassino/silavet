@php
    use App\Support\PrecioInput;
@endphp

<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Listados estadísticos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Estimación de costos</h1>
                <p class="mt-2 text-sm text-white/80">
                    Seleccione un cliente y agregue determinaciones para calcular el costo y ver los procedimientos de muestra.
                </p>
            </x-vl-hero-heading>
        </div>
    </div>

    <div class="vl-card space-y-5 p-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="vl-est-costos-cliente">Cliente *</label>
                <select wire:model.live="idClientes"
                        id="vl-est-costos-cliente"
                        class="form-input"
                        @disabled($clienteBloqueado)>
                    <option value="">Seleccione</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
                @if ($idClientes)
                    <p class="mt-1 text-xs text-neutral-500">
                        Descuento del cliente:
                        <span class="font-semibold tabular-nums">{{ number_format($porcentajeDescuento, 2, ',', '.') }} %</span>
                    </p>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap justify-center">
            <button type="button"
                    class="btn-secondary"
                    @disabled(count($seleccionados) === 0)
                    x-on:click="window.vlSwalConfirmar('¿Borrar todas las determinaciones seleccionadas?', 'Borrar lista', { confirmButtonText: 'Sí, borrar', icon: 'warning' }).then(ok => ok && $wire.borrarTodas())">
                Borrar todas las determinaciones seleccionadas
            </button>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-lg border border-accent-200 p-4">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-700">
                    Listado de determinaciones
                </h2>
                <label class="form-label" for="vl-est-costos-tipo">Agregar determinación</label>
                <select wire:model.live="idTipoSeleccionar"
                        id="vl-est-costos-tipo"
                        class="form-input"
                        @disabled(! $idClientes)>
                    <option value="">Seleccione</option>
                    @foreach ($tiposDisponibles as $tipo)
                        <option value="{{ $tipo->idTipodeterminaciones }}">{{ $tipo->nombre }}</option>
                    @endforeach
                </select>
                @unless ($idClientes)
                    <p class="mt-2 text-xs text-amber-700">Primero elija un cliente.</p>
                @endunless
            </div>

            <div class="rounded-lg border border-accent-200 p-4">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-700">
                    Determinaciones seleccionadas
                </h2>
                @if (count($seleccionados) === 0)
                    <p class="text-sm text-neutral-500">Ninguna determinación seleccionada.</p>
                @else
                    <ul class="divide-y divide-accent-100">
                        @foreach ($seleccionados as $indice => $fila)
                            <li class="flex items-start gap-3 py-2" wire:key="est-det-{{ $fila['idTipodeterminaciones'] }}-{{ $indice }}">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-neutral-800">{{ $fila['nombre'] }}</p>
                                    <p class="mt-0.5 text-xs text-neutral-500 tabular-nums">
                                        Lista {{ $fila['neto'] }}
                                        @if (PrecioInput::parse($fila['descuento']) > 0)
                                            · Dto. {{ $fila['descuento'] }}
                                        @endif
                                        · <span class="font-semibold text-neutral-700">{{ $fila['precio'] }}</span>
                                    </p>
                                </div>
                                <button type="button"
                                        class="shrink-0 text-xs font-semibold text-red-700 hover:text-red-900"
                                        wire:click="quitarDeterminacion({{ $indice }})"
                                        title="Quitar de la lista">
                                    Quitar
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-accent-50 px-4 py-3">
            <p class="text-sm font-semibold uppercase tracking-wide text-neutral-700">
                Suma total de las determinaciones seleccionadas
            </p>
            <p class="text-lg font-bold tabular-nums text-primary-800">{{ $sumaTotalFormateada }}</p>
        </div>

        <div class="rounded-lg border border-accent-300 p-4">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-800">
                Procedimientos para las muestras a enviar
            </h2>
            @if (count($requerimientos) === 0)
                <p class="text-sm text-neutral-500">
                    @if (count($seleccionados) === 0)
                        Agregue determinaciones para ver los procedimientos de toma de muestra.
                    @else
                        No hay requerimientos asociados a las determinaciones seleccionadas.
                    @endif
                </p>
            @else
                <div class="space-y-5">
                    @foreach ($requerimientos as $req)
                        <section>
                            @if ($req['titulo'] !== '')
                                <h3 class="mb-2 text-sm font-bold uppercase text-red-700 underline">
                                    {{ $req['titulo'] }}
                                </h3>
                            @endif
                            <div class="prose prose-sm max-w-none text-neutral-800 vl-requerimiento-html">
                                {!! $req['html'] !!}
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
