@php
    use App\Support\PrecioInput;
@endphp

<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Listados estadísticos</p>
                <h1 class="text-2xl font-bold sm:text-3xl">Estimación de costos</h1>
                <p class="mt-2 text-sm text-white/80">
                    Seleccione un cliente y agregue determinaciones para calcular el costo y ver los procedimientos de muestra.
                </p>
            </x-vl-hero-heading>
            <x-vl-cli-avisos-campana />
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
                @if ($idClientes && $resumenDescuento)
                    <p class="mt-1 text-xs text-neutral-500">
                        {{ $resumenDescuento['texto'] }}
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
                <div x-data="vlEstCostosBusqueda">
                    <label class="form-label" for="vl-est-costos-buscar">Agregar determinación</label>
                    <input wire:model.live.debounce.400ms="busquedaDeterminacion"
                           id="vl-est-costos-buscar"
                           x-ref="buscar"
                           type="search"
                           placeholder="Escriba para buscar…"
                           class="form-input"
                           autocomplete="off"
                           title="Escriba, use ↑↓ y Enter para agregar"
                           @keydown.arrow-down.prevent="mover(1)"
                           @keydown.arrow-up.prevent="mover(-1)"
                           @keydown.enter.prevent="elegirIndice()"
                           @keydown.escape.prevent="$wire.set('busquedaDeterminacion', '')"
                           @disabled(! $idClientes)>
                    @unless ($idClientes)
                        <p class="mt-2 text-xs text-amber-700">Primero elija un cliente.</p>
                    @else
                        @if (trim($busquedaDeterminacion) === '')
                            <p class="mt-2 text-xs text-neutral-500">
                                Escriba unas letras; la búsqueda se hace al pausar.
                                Con resultados: <strong class="font-semibold text-neutral-700">↑↓</strong> y
                                <strong class="font-semibold text-neutral-700">Enter</strong> para agregar.
                            </p>
                        @elseif ($tiposDisponibles->isEmpty())
                            <p class="mt-2 text-xs text-neutral-500">No hay determinaciones que coincidan.</p>
                        @else
                            <ul x-ref="lista"
                                class="mt-2 max-h-56 overflow-y-auto divide-y divide-accent-100 rounded-lg border border-accent-200"
                                role="listbox"
                                aria-label="Resultados de búsqueda">
                                @foreach ($tiposDisponibles as $tipo)
                                    <li wire:key="est-buscar-{{ $tipo->idTipodeterminaciones }}"
                                        role="option"
                                        data-est-id="{{ $tipo->idTipodeterminaciones }}"
                                        data-est-idx="{{ $loop->index }}"
                                        :aria-selected="indice === {{ $loop->index }}"
                                        @mouseenter="indice = {{ $loop->index }}"
                                        @mousedown.prevent="elegirId({{ $tipo->idTipodeterminaciones }})"
                                        :class="indice === {{ $loop->index }}
                                            ? 'bg-primary-600 font-semibold text-white'
                                            : 'text-neutral-800 hover:bg-accent-50'"
                                        class="cursor-pointer px-3 py-2 text-sm">
                                        {{ $tipo->nombre }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endunless
                </div>
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
