<div class="vl-page vl-page--wide"
     x-data="{
        estado: 'idle', // idle | ok | error
        mensaje: '',
        timer: null,
        extraerCodigo(texto) {
            const raw = (texto ?? '').toString();
            // Acepta scripts pegados como HTML (<script>...</script>).
            const m = raw.match(/<script\b[^>]*>([\s\S]*?)<\/script>/i);
            if (m && typeof m[1] === 'string') {
                return m[1].trim();
            }
            return raw;
        },
        validarAhora(code) {
            const texto = this.extraerCodigo(code);
            if (texto.trim() === '') {
                this.estado = 'idle';
                this.mensaje = 'Sin script (vacío).';
                return;
            }

            try {
                // Compila sin ejecutar el código.
                // eslint-disable-next-line no-new-func
                new Function(texto);
                this.estado = 'ok';
                this.mensaje = 'Sintaxis OK.';
            } catch (e) {
                this.estado = 'error';
                this.mensaje = (e && e.message) ? e.message : 'Error de sintaxis.';
            }
        },
        validarDebounce(code) {
            window.clearTimeout(this.timer);
            this.timer = window.setTimeout(() => this.validarAhora(code), 350);
        },
     }"
     x-init="validarAhora(@js($formulas))">
    <div class="vl-hero vl-hero--compact shrink-0">
        <div class="vl-hero-inner">
            <x-vl-hero-heading>
                <p class="vl-eyebrow">Administración</p>
                <h1 class="text-xl font-bold sm:text-2xl">Script de Automatización</h1>
                <p class="mt-1 max-w-3xl text-xs text-white/80 sm:text-sm">
                    Edita <code class="rounded bg-white/10 px-1 py-0.5">entorno.formulas</code>. Se valida sintaxis en el navegador mientras escribís
                    (sin ejecutar el script). Si pegás <code class="rounded bg-white/10 px-1 py-0.5">&lt;script&gt;...&lt;/script&gt;</code>,
                    se valida el contenido interno.
                </p>
            </x-vl-hero-heading>
        </div>
    </div>

    <form wire:submit.prevent="save" class="vl-card p-4">
        <div class="grid gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-neutral-800">Estado</span>
                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold"
                      :class="{
                        'bg-neutral-100 text-neutral-700': estado === 'idle',
                        'bg-emerald-100 text-emerald-800': estado === 'ok',
                        'bg-red-100 text-red-800': estado === 'error',
                      }"
                      x-text="estado === 'idle' ? 'Sin validar' : (estado === 'ok' ? 'OK' : 'Error')"></span>
                <span class="text-xs text-neutral-600" x-text="mensaje"></span>
            </div>

            <div>
                <label class="form-label mb-1" for="formulas">Script (JavaScript)</label>
                <textarea id="formulas"
                          rows="18"
                          class="form-input font-mono text-xs leading-5"
                          spellcheck="false"
                          wire:model.live.debounce.150ms="formulas"
                          x-on:input="validarDebounce($event.target.value)"></textarea>
                @error('formulas') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <button type="submit"
                        class="btn-primary py-1.5 text-sm"
                        wire:loading.attr="disabled"
                        wire:target="save">
                    Guardar
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn-secondary py-1.5 text-sm">Volver</a>
            </div>
        </div>
    </form>
</div>
