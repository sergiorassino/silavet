<div class="vl-page vl-page--wide">
    <div class="vl-hero vl-hero--compact mb-3">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Protocolos</p>
                <h1>{{ $titulo }}</h1>
                <x-vl-hero-usuario />
            </x-vl-hero-heading>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card vl-form--compact max-w-5xl">
        <div class="vl-form-actions">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">Guardar</button>
            <a href="{{ $urlVolver }}" class="btn-secondary">Cancelar</a>
            @if ($idPacientes)
                <button type="button"
                        class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                        @disabled(! $puedeEliminar)
                        wire:loading.attr="disabled"
                        wire:target="eliminar"
                        @if ($puedeEliminar)
                            title="Eliminar protocolo"
                            x-on:click="window.vlSwalConfirmar('¿Eliminar este protocolo? Esta acción no se puede deshacer.', 'Eliminar protocolo', { confirmButtonText: 'Sí, eliminar', icon: 'warning' }).then(ok => ok && $wire.eliminar())"
                        @else
                            title="No se puede eliminar: el protocolo tiene determinaciones cargadas"
                        @endif>
                    Eliminar
                </button>
                <span class="self-center text-[10px] leading-tight text-neutral-500">
                    No se puede eliminar un paciente que tiene determinaciones cargadas
                </span>
            @endif
        </div>

        @if ($mostrarListaPrecios && ! $tieneColumnaListaPrecios)
            <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                La lista de precios se guarda en la columna <strong>listaPreciosPaciente</strong>,
                que no existe en esta base. Ejecute el script SQL de migración antes de usarla.
            </div>
        @endif

        <div class="vl-form-grid">
            @if ($mostrarListaPrecios)
                <div class="vl-form-field">
                    <label class="form-label" for="listaPreciosPaciente">Lista de precios *</label>
                    <select wire:model="listaPreciosPaciente"
                            id="listaPreciosPaciente"
                            class="form-input">
                        @foreach ($opcionesListaPrecios as $nro => $etiqueta)
                            <option value="{{ $nro }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    @error('listaPreciosPaciente') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="vl-form-field vl-form-span-2">
                <label class="form-label" for="vl-cliente-input">Cliente *</label>
                @if ($clienteBloqueado)
                    {{-- Usuario cliente: muestra el nombre fijo, sin búsqueda --}}
                    <input id="vl-cliente-input"
                           type="text"
                           class="form-input"
                           disabled
                           value="{{ $clientes->first()?->nombre ?? '' }}">
                @else
                    @php
                        $clienteOpciones = $clientes
                            ->map(fn ($c) => ['id' => (string) $c->idClientes, 'nombre' => $c->nombre])
                            ->values()
                            ->all();
                        $clienteNombreInicial = $clientes->firstWhere('idClientes', $idClientes)?->nombre ?? '';
                    @endphp
                    <div class="vl-search-select"
                         wire:ignore
                         x-data="vlSearchSelect({
                             opciones: @js($clienteOpciones),
                             idInicial: @js((string) ($idClientes ?? '')),
                             nombreInicial: @js($clienteNombreInicial),
                             propiedad: 'idClientes',
                         })"
                         @click.outside="alSalir()">
                        <div class="vl-search-select-wrapper">
                            <input id="vl-cliente-input"
                                   x-ref="input"
                                   type="text"
                                   class="form-input w-full"
                                   :class="idSeleccionado !== '' ? 'pr-8' : ''"
                                   placeholder="Escriba para buscar…"
                                   autocomplete="off"
                                   spellcheck="false"
                                   x-model="consulta"
                                   @focus="abrir()"
                                   @input="onInput()"
                                   @keydown="onKeydown($event)">
                            <button type="button"
                                    class="vl-search-select-clear"
                                    x-show="idSeleccionado !== ''"
                                    x-cloak
                                    title="Limpiar selección"
                                    aria-label="Limpiar selección"
                                    @mousedown.prevent="limpiar()">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <ul x-ref="lista"
                            class="vl-search-select-lista"
                            x-show="abierto"
                            x-cloak
                            role="listbox"
                            aria-label="Opciones de cliente">
                            <template x-for="(item, index) in filtrados" :key="item.id">
                                <li role="option"
                                    :data-combo-idx="index"
                                    class="vl-search-select-item"
                                    :class="indice === index ? 'is-active' : ''"
                                    :aria-selected="indice === index"
                                    @mouseenter="indice = index"
                                    @mousedown.prevent="elegir(item)"
                                    x-text="item.nombre"></li>
                            </template>
                            <li x-show="filtrados.length === 0"
                                class="vl-search-select-vacio">Sin coincidencias</li>
                        </ul>
                    </div>
                @endif
                @error('idClientes') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field">
                <label class="form-label" for="idUsuarios">Médico solicitante</label>
                <select wire:model="idUsuarios"
                        id="idUsuarios"
                        class="form-input"
                        @disabled(! $idClientes)>
                    <option value="">Seleccione</option>
                    @foreach ($medicos as $medico)
                        <option value="{{ $medico->idUsuarios }}">{{ $medico->apenom }}</option>
                    @endforeach
                </select>
                @error('idUsuarios') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field">
                <label class="form-label" for="fechhoy">Fecha *</label>
                <input wire:model.live="fechhoy"
                       id="fechhoy"
                       type="date"
                       class="form-input">
                @error('fechhoy') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            @if ($usaTipoProtocolo && ! $idPacientes)
                <div class="vl-form-field">
                    <label class="form-label" for="tipoProtocolo">Tipo de protocolo *</label>
                    <select wire:model.live="tipoProtocolo"
                            id="tipoProtocolo"
                            class="form-input">
                        <option value="L">Protocolo largo</option>
                        <option value="C">Protocolo corto</option>
                    </select>
                    @error('tipoProtocolo') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="vl-form-field">
                <label class="form-label" for="nombreProtocolo">Protocolo @unless ($dejaNombreProtocoloVacio)*@endunless</label>
                <input wire:model="nombreProtocolo"
                       id="nombreProtocolo"
                       type="text"
                       maxlength="50"
                       class="form-input font-semibold"
                       @unless ($nombreProtocoloEditable) readonly @endunless>
                @unless ($idPacientes)
                    @if ($dejaNombreProtocoloVacio)
                        <p class="mt-0.5 text-[10px] leading-tight text-neutral-500">Opcional — sin número automático.</p>
                    @elseif ($esConsecutivoSimple)
                        <p class="mt-0.5 text-[10px] leading-tight text-neutral-500">Sugerido — puede modificarlo antes de guardar.</p>
                    @else
                        <p class="mt-0.5 text-[10px] leading-tight text-neutral-500">Provisional — se confirma al guardar.</p>
                    @endif
                @endunless
                @error('nombreProtocolo') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field">
                <label class="form-label" for="nombre">Nombre *</label>
                <input wire:model="nombre" id="nombre" type="text" class="form-input">
                @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field">
                <label class="form-label" for="propietario">Tutor</label>
                <input wire:model="propietario" id="propietario" type="text" class="form-input">
                @error('propietario') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field">
                <label class="form-label" for="dni">D.N.I.</label>
                <input wire:model.live="dni"
                       id="dni"
                       type="text"
                       maxlength="8"
                       inputmode="numeric"
                       class="form-input"
                       autocomplete="off">
                @error('dni') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field">
                <label class="form-label" for="cuit">CUIT</label>
                <input wire:model.live="cuit"
                       id="cuit"
                       type="text"
                       maxlength="13"
                       inputmode="numeric"
                       class="form-input"
                       placeholder="99-99999999-9"
                       autocomplete="off">
                @error('cuit') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field vl-form-span-2">
                <label class="form-label" for="email">Email</label>
                <input wire:model="email" id="email" type="email" class="form-input">
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field">
                <label class="form-label" for="whatsapp">Whatsapp</label>
                <input wire:model="whatsapp" id="whatsapp" type="text" class="form-input">
                @error('whatsapp') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field">
                <label class="form-label" for="idEspecies">Especie *</label>
                <select wire:model.live="idEspecies" id="idEspecies" class="form-input">
                    <option value="">Seleccione</option>
                    @foreach ($especies as $especie)
                        <option value="{{ $especie->idEspecies }}">{{ $especie->nombre }}</option>
                    @endforeach
                </select>
                @error('idEspecies') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field">
                <label class="form-label" for="idRazas">Raza</label>
                <select wire:model="idRazas"
                        id="idRazas"
                        class="form-input"
                        @disabled(! $idEspecies)>
                    <option value="">Seleccione</option>
                    @foreach ($razas as $raza)
                        <option value="{{ $raza->idRazas }}">{{ $raza->nombre }}</option>
                    @endforeach
                </select>
                @error('idRazas') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field">
                <label class="form-label" for="sexo">Sexo</label>
                <select wire:model="sexo" id="sexo" class="form-input">
                    <option value="">Seleccione</option>
                    @foreach ($sexos as $opcion)
                        <option value="{{ $opcion }}">{{ $opcion }}</option>
                    @endforeach
                </select>
                @error('sexo') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field">
                <label class="form-label" for="edad">Edad</label>
                <input wire:model="edad" id="edad" type="text" class="form-input">
                @error('edad') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="vl-form-field vl-form-span-full">
                <label class="form-label" for="observaciones">Observaciones</label>
                <textarea wire:model="observaciones"
                          id="observaciones"
                          rows="3"
                          class="form-input"></textarea>
                @error('observaciones') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </form>
</div>
