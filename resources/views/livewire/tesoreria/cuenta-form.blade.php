<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <div>
                <p class="vl-eyebrow">Tesorería</p>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $titulo }}</h1>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card mx-auto w-full max-w-md p-4">
        <div class="grid gap-3">
            <div>
                <label class="form-label mb-1" for="nombreCuenta">Nombre de la cuenta *</label>
                <input wire:model="nombreCuenta" id="nombreCuenta" type="text" maxlength="80" class="form-input py-1.5 text-sm">
                @error('nombreCuenta') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <button type="submit" class="btn-primary py-1.5 text-sm" wire:loading.attr="disabled">Guardar</button>
                <a href="{{ route('tesoreria.cuentas.index') }}" class="btn-secondary py-1.5 text-sm">Cancelar</a>
            </div>
        </div>
    </form>
</div>
