<div class="vl-page">
    <div class="vl-hero mb-4">
        <div class="vl-hero-inner">
            <div>
                <p class="vl-eyebrow">ABM</p>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $tituloPagina }}</h1>
            </div>
        </div>
    </div>

    <div class="vl-card mx-auto w-full max-w-3xl space-y-4 p-4">
        <div>
            <label class="form-label mb-1" for="titulo">Título *</label>
            <input wire:model="titulo" id="titulo" type="text" maxlength="30" class="form-input py-1.5 text-sm" autofocus>
            @error('titulo') <p class="form-error">{{ $message }}</p> @enderror
            <p class="mt-1 text-[11px] text-neutral-500">Máximo 30 caracteres (campo legacy).</p>
        </div>

        <x-vl-rich-text-editor
            wire-property="requerimiento"
            :initial="$requerimiento"
            :max-length="$htmlMax"
            label="Procedimiento de toma de muestra *"
            placeholder="Describa cómo debe tomarse la muestra…"
            toolbar-aria-label="Formato del procedimiento"
            save-method="save"
            surface-class="vl-rich-editor-surface--tall"
        >
            <div class="mt-4 flex flex-wrap gap-2 border-t border-accent-200 pt-3">
                <button type="button"
                        @click="guardar()"
                        class="btn-primary py-1.5 text-sm"
                        wire:loading.attr="disabled"
                        wire:target="save">
                    <span wire:loading.remove wire:target="save">Guardar</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </button>
                <a href="{{ route('abm.requerimientos.index') }}" class="btn-secondary py-1.5 text-sm">Cancelar</a>
            </div>
        </x-vl-rich-text-editor>
    </div>
</div>
