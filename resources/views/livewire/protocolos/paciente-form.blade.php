<div class="vl-page vl-page--wide">
    <div class="vl-hero vl-hero--compact mb-3">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Protocolos</p>
                <h1>{{ $titulo }}</h1>
            </x-vl-hero-heading>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card vl-form--compact max-w-5xl">
        <div class="vl-form-actions">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">Guardar</button>
            <a href="{{ $urlVolver }}" class="btn-secondary">Cancelar</a>
        </div>

        <div class="vl-form-grid">
            <div class="vl-form-field vl-form-span-2">
                <label class="form-label" for="idClientes">Cliente *</label>
                <select wire:model.live="idClientes"
                        id="idClientes"
                        class="form-input"
                        @disabled($clienteBloqueado)>
                    <option value="">Seleccione</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->idClientes }}">{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
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
                <label class="form-label" for="nombreProtocolo">Protocolo *</label>
                <input wire:model="nombreProtocolo"
                       id="nombreProtocolo"
                       type="text"
                       class="form-input font-semibold"
                       readonly>
                @unless ($idPacientes)
                    <p class="mt-0.5 text-[10px] leading-tight text-neutral-500">Provisional — se confirma al guardar.</p>
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
