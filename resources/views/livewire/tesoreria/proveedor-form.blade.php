<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Tesorería</p>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $titulo }}</h1>
            </x-vl-hero-heading>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card mx-auto w-full max-w-md p-4">
        <div class="grid gap-3">
            <div>
                <label class="form-label mb-1" for="idConceptos">Concepto *</label>
                <select wire:model="idConceptos" id="idConceptos" class="form-input py-1.5 text-sm" autofocus>
                    <option value="">Seleccionar…</option>
                    @foreach ($conceptos as $concepto)
                        <option value="{{ $concepto->id }}">{{ $concepto->concepto }}</option>
                    @endforeach
                </select>
                @error('idConceptos') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label mb-1" for="proveedor">Proveedor *</label>
                <input wire:model="proveedor" id="proveedor" type="text" maxlength="200" class="form-input py-1.5 text-sm">
                @error('proveedor') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label mb-1" for="cuit">CUIT</label>
                <input wire:model.live="cuit" id="cuit" type="text" maxlength="13" inputmode="numeric"
                       placeholder="99-99999999-9" class="form-input py-1.5 text-sm tabular-nums">
                @error('cuit') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <button type="submit" class="btn-primary py-1.5 text-sm" wire:loading.attr="disabled">Guardar</button>
                <a href="{{ route('tesoreria.proveedores.index') }}" class="btn-secondary py-1.5 text-sm">Cancelar</a>
            </div>
        </div>
    </form>
</div>
