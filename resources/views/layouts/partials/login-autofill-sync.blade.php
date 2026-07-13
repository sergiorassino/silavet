{{--
    Autocompletar del navegador rellena el DOM pero no dispara wire:model.
    Sincroniza valores al componente Livewire del formulario de login.
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

        const syncAutofill = async () => {
            const dniVal = readField('dni').replace(/[^a-zA-Z0-9]/g, '').slice(0, 10);
            const passwordVal = readField('password');

            if (dniVal !== '' && $wire.get('dni') !== dniVal) {
                await $wire.set('dni', dniVal);
            }

            if (passwordVal !== '' && $wire.get('password') !== passwordVal) {
                await $wire.set('password', passwordVal);
            }
        };

        const scheduleSyncAutofill = () => {
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
            if (boot.started) {
                return;
            }
            boot.started = true;
            [100, 300, 600, 1200].forEach((ms) => window.setTimeout(scheduleSyncAutofill, ms));
        };

        document.addEventListener('livewire:initialized', boot, { once: true });
        window.setTimeout(boot, 150);
    })();
</script>
@endscript
