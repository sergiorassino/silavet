<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">ABM · Gestión de Clientes</p>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $titulo }}</h1>
            </x-vl-hero-heading>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card mx-auto w-full max-w-3xl p-4 sm:p-6">
        <div class="grid gap-4">
            <div>
                <label class="form-label mb-1" for="nombre">Nombre *</label>
                <input wire:model="nombre" id="nombre" type="text" maxlength="200" class="form-input py-1.5 text-sm" autofocus>
                @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label mb-1" for="direccion">Dirección</label>
                <input wire:model="direccion" id="direccion" type="text" maxlength="200" class="form-input py-1.5 text-sm">
                @error('direccion') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label mb-1" for="telefono1">Teléfono 1</label>
                    <input wire:model="telefono1" id="telefono1" type="text" maxlength="50" class="form-input py-1.5 text-sm">
                    @error('telefono1') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label mb-1" for="telefono2">Teléfono 2</label>
                    <input wire:model="telefono2" id="telefono2" type="text" maxlength="50" class="form-input py-1.5 text-sm">
                    @error('telefono2') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label mb-1" for="email">Email</label>
                    <input wire:model="email" id="email" type="email" maxlength="150" class="form-input py-1.5 text-sm">
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label mb-1" for="whatsapp">WhatsApp</label>
                    <input wire:model="whatsapp" id="whatsapp" type="text" maxlength="20" class="form-input py-1.5 text-sm">
                    @error('whatsapp') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label mb-1" for="cuit">CUIT</label>
                    <input wire:model.live="cuit" id="cuit" type="text" maxlength="13" inputmode="numeric"
                           class="form-input max-w-xs py-1.5 text-sm" placeholder="99-99999999-9">
                    @error('cuit') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label mb-1" for="descuento">Descuento (%)</label>
                    <input wire:model="descuento" id="descuento" type="text" inputmode="decimal"
                           class="form-input max-w-[8rem] py-1.5 text-sm" placeholder="0">
                    @error('descuento') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <button type="submit" class="btn-primary py-1.5 text-sm" wire:loading.attr="disabled">Guardar</button>
                <a href="{{ route('abm.clientes.index') }}" class="btn-secondary py-1.5 text-sm">Cancelar</a>
            </div>
        </div>
    </form>
</div>
