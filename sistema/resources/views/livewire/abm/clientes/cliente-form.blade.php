<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <h1 class="text-2xl font-bold text-neutral-800">{{ $titulo }}</h1>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card p-6 space-y-5 max-w-3xl">
        <div>
            <label class="form-label" for="nombre">Nombre *</label>
            <input wire:model="nombre" id="nombre" type="text" class="form-input">
            @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="form-label" for="direccion">Dirección</label>
            <input wire:model="direccion" id="direccion" type="text" class="form-input">
            @error('direccion') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="telefono1">Teléfono 1</label>
                <input wire:model="telefono1" id="telefono1" type="text" class="form-input">
                @error('telefono1') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="telefono2">Teléfono 2</label>
                <input wire:model="telefono2" id="telefono2" type="text" class="form-input">
                @error('telefono2') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label" for="email">Email</label>
                <input wire:model="email" id="email" type="email" class="form-input">
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="whatsapp">WhatsApp</label>
                <input wire:model="whatsapp" id="whatsapp" type="text" class="form-input">
                @error('whatsapp') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="form-label" for="cuit">CUIT</label>
            <input wire:model="cuit" id="cuit" type="text" maxlength="11" inputmode="numeric" class="form-input max-w-xs">
            @error('cuit') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">Guardar</button>
            <a href="{{ route('abm.clientes.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
