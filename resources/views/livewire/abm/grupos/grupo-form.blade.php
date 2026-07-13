<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <div>
                <p class="vl-eyebrow">ABM</p>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $titulo }}</h1>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card mx-auto w-full max-w-md p-4">
        <div class="grid gap-3">
            <div>
                <label class="form-label mb-1" for="nombreGrupo">Nombre del grupo *</label>
                <input wire:model="nombreGrupo" id="nombreGrupo" type="text" maxlength="50" class="form-input py-1.5 text-sm">
                @error('nombreGrupo') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="form-label mb-1" for="orden">Orden</label>
                <input wire:model="orden" id="orden" type="number" min="0" max="9999" class="form-input max-w-[6rem] py-1.5 text-sm">
                @error('orden') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <button type="submit" class="btn-primary py-1.5 text-sm" wire:loading.attr="disabled">Guardar</button>
                <a href="{{ route('admin.grupos.index') }}" class="btn-secondary py-1.5 text-sm">Cancelar</a>
            </div>
        </div>
    </form>
</div>
