{{--
    Autocompletar del navegador rellena el DOM pero no dispara wire:model.
    Sincroniza al estado Livewire en local (sin POST) para no competir con login().

    Importante: un $wire.set() “vivo” concurrente con login() puede provocar 419 CSRF
    (Verificando… → recarga a /login). Tras regenerar sesión en login exitoso, ese
    request fantasma también puede devolver al usuario a la pantalla de acceso.
--}}
@script
<script>
    (() => {
        const form = $wire.$el.querySelector('form');
        if (!form) {
            return;
        }

        const readField = (id) => {
            const el = form.querySelector('#' + id);
            return el ? String(el.value || '') : '';
        };

        let syncTimer = null;
        const bootTimers = [];
        let locked = false;

        const clearTimers = () => {
            if (syncTimer) {
                window.clearTimeout(syncTimer);
                syncTimer = null;
            }
            while (bootTimers.length) {
                window.clearTimeout(bootTimers.pop());
            }
        };

        const lock = () => {
            locked = true;
            clearTimers();
        };

        window.__vlLoginAutofillLock = lock;
        window.addEventListener('vl-login-submit', lock, { once: true });
        form.addEventListener('submit', lock, true);

        const syncAutofill = () => {
            if (locked) {
                return;
            }

            const dniVal = readField('dni').replace(/[^a-zA-Z0-9]/g, '').slice(0, 10);
            const passwordVal = readField('password');

            // live=false: solo estado local; el POST único ocurre en login().
            if (dniVal !== '' && $wire.get('dni') !== dniVal) {
                $wire.set('dni', dniVal, false);
            }

            if (passwordVal !== '' && $wire.get('password') !== passwordVal) {
                $wire.set('password', passwordVal, false);
            }
        };

        const scheduleSyncAutofill = () => {
            if (locked) {
                return;
            }
            if (syncTimer) {
                window.clearTimeout(syncTimer);
            }
            syncTimer = window.setTimeout(syncAutofill, 120);
        };

        ['change', 'input'].forEach((eventName) => {
            form.querySelector('#dni')?.addEventListener(eventName, scheduleSyncAutofill);
            form.querySelector('#password')?.addEventListener(eventName, scheduleSyncAutofill);
        });

        const boot = () => {
            if (boot.started || locked) {
                return;
            }
            boot.started = true;
            // Pocos intentos: el password manager rellena en distintos momentos.
            [100, 400, 900].forEach((ms) => {
                bootTimers.push(window.setTimeout(scheduleSyncAutofill, ms));
            });
        };

        document.addEventListener('livewire:initialized', boot, { once: true });
        bootTimers.push(window.setTimeout(boot, 150));
    })();
</script>
@endscript
