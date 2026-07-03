<div>
    <div class="vl-auth-card p-6 sm:p-8">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-800">Iniciar sesión</h2>
        <p class="mt-1.5 text-sm text-neutral-600">Ingrese sus datos de acceso al laboratorio.</p>

        @if (session('error'))
            <div class="mt-5 p-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit.prevent="login" class="mt-6 space-y-4" autocomplete="on">
            <div>
                <label class="vl-auth-label" for="dni">DNI (usuario)</label>
                <input wire:model.live.debounce.300ms="dni"
                       id="dni"
                       type="text"
                       inputmode="numeric"
                       maxlength="11"
                       autocomplete="username"
                       class="vl-auth-input @error('dni') !border-red-400 ring-2 ring-red-200/80 @enderror">
                @error('dni')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="vl-auth-label" for="password">Contraseña</label>
                <input wire:model="password"
                       id="password"
                       type="password"
                       autocomplete="current-password"
                       class="vl-auth-input @error('password') !border-red-400 ring-2 ring-red-200/80 @enderror">
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-1">
                <button type="submit" class="vl-auth-btn" wire:loading.attr="disabled" wire:target="login">
                    <span wire:loading.remove wire:target="login">Ingresar al sistema</span>
                    <span wire:loading wire:target="login">Verificando…</span>
                </button>
            </div>
        </form>
    </div>
</div>
