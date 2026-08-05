import './bootstrap';
import Swal from 'sweetalert2';
import './cantidad-determinaciones-comparac';
import { instalarHemogramaAuto } from './hemograma-auto';

window.Swal = Swal;

function vlNormalizarHtmlEditor(html) {
    const limpio = String(html || '')
        .replace(/<div><br><\/div>/gi, '')
        .replace(/<p><br><\/p>/gi, '')
        .replace(/&nbsp;/gi, ' ')
        .trim();

    if (limpio === '' || limpio === '<p></p>' || limpio === '<br>' || limpio === '<div></div>') {
        return '';
    }

    return limpio;
}

const VL_EDITOR_COLORS = [
    '#000000', '#e60000', '#ff9900', '#ffff00', '#008a00', '#0066cc', '#9933ff',
    '#ffffff', '#bbbbbb', '#f06666', '#ffc266', '#66b966', '#66a3e0', '#c285ff',
];


window.vlSwalExito = (mensaje, titulo = 'Listo') => {
    return Swal.fire({
        icon: 'success',
        title: titulo,
        text: mensaje,
        confirmButtonColor: '#0284c7',
    });
};

window.vlSwalError = (mensaje, titulo = 'Error') => {
    return Swal.fire({
        icon: 'error',
        title: titulo,
        text: mensaje,
        confirmButtonColor: '#0284c7',
    });
};

window.vlSwalConfirmar = function (mensaje, titulo = '¿Confirma?', opciones = {}) {
    if (typeof Swal === 'undefined') {
        return Promise.resolve(window.confirm(mensaje));
    }

    return Swal.fire({
        icon: 'question',
        title: titulo,
        text: mensaje,
        showCancelButton: true,
        confirmButtonText: opciones.confirmButtonText ?? 'Sí, confirmar',
        cancelButtonText: opciones.cancelButtonText ?? 'Cancelar',
        confirmButtonColor: '#0284c7',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusCancel: true,
        ...opciones,
    }).then((result) => result.isConfirmed === true);
};

/**
 * Pide un entero (p. ej. cantidad de etiquetas). Devuelve el número o null si cancela.
 */
window.vlSwalPedirCantidad = async function (opciones = {}) {
    if (typeof Swal === 'undefined') {
        const raw = window.prompt(opciones.mensaje ?? 'Cantidad', String(opciones.valor ?? 2));
        if (raw === null) {
            return null;
        }
        const n = parseInt(String(raw).trim(), 10);
        return Number.isFinite(n) && n >= 1 ? n : null;
    }

    const min = Number(opciones.min ?? 1);
    const max = Number(opciones.max ?? 99);
    const result = await Swal.fire({
        icon: 'question',
        title: opciones.titulo ?? 'Cantidad',
        text: opciones.mensaje ?? 'Ingrese la cantidad',
        input: 'number',
        inputValue: opciones.valor ?? 2,
        inputAttributes: {
            min: String(min),
            max: String(max),
            step: '1',
        },
        showCancelButton: true,
        confirmButtonText: opciones.confirmButtonText ?? 'Imprimir',
        cancelButtonText: opciones.cancelButtonText ?? 'Cancelar',
        confirmButtonColor: '#0284c7',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        inputValidator: (value) => {
            const n = parseInt(String(value ?? '').trim(), 10);
            if (!Number.isFinite(n) || n < min || n > max) {
                return `Ingrese un número entre ${min} y ${max}.`;
            }
            return undefined;
        },
    });

    if (!result.isConfirmed) {
        return null;
    }

    const n = parseInt(String(result.value ?? '').trim(), 10);
    return Number.isFinite(n) ? n : null;
};

document.addEventListener('livewire:init', () => {
    Livewire.on('vl-swal-exito', ({ mensaje, titulo }) => {
        window.vlSwalExito(mensaje, titulo ?? 'Listo');
    });

    Livewire.on('vl-swal-error', ({ mensaje, titulo }) => {
        window.vlSwalError(mensaje, titulo ?? 'Error');
    });

    Livewire.on('vl-abrir-url', ({ url }) => {
        if (!url || typeof url !== 'string') {
            return;
        }
        window.open(url, '_blank', 'noopener,noreferrer');
    });

    Livewire.on('vl-ia-chatgpt', ({ prompt, url }) => {
        if (!prompt || typeof prompt !== 'string') {
            return;
        }

        const base = (url && typeof url === 'string') ? url : 'https://chatgpt.com';
        const maxUrl = 16000;
        const encoded = encodeURIComponent(prompt);
        let destino = `${base}/#?q=${encoded}`;
        if (destino.length > maxUrl) {
            destino = `${base}/?q=${encoded}`;
        }
        if (destino.length > maxUrl) {
            destino = base;
        }

        const preabierta = window.__vlIaChatWin;
        window.__vlIaChatWin = null;

        if (preabierta && !preabierta.closed) {
            preabierta.location = destino;
        } else {
            window.open(destino, '_blank', 'noopener,noreferrer');
        }

        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(prompt).catch(() => {});
        }
    });

    Livewire.on('vl-ia-chatgpt-cancelar', () => {
        if (window.__vlIaChatWin && !window.__vlIaChatWin.closed) {
            window.__vlIaChatWin.close();
        }
        window.__vlIaChatWin = null;
    });

    // 419 CSRF en subcarpeta / PWA: mismo patrón que Sistemas Escolares.
    // Evita el diálogo nativo en bucle; recarga con cache-bust y, si falla de nuevo, avisa.
    if (typeof Livewire.interceptRequest === 'function') {
        let vl419Recargando = false;

        Livewire.interceptRequest(({ onError }) => {
            onError(({ response, preventDefault }) => {
                if (response.status !== 419) {
                    return;
                }

                preventDefault();

                if (vl419Recargando) {
                    return;
                }

                const storageKey = 'vl-lw419-recarga';
                const ahora = Date.now();
                const ultima = Number.parseInt(sessionStorage.getItem(storageKey) || '0', 10);

                if (ultima > 0 && ahora - ultima < 4000) {
                    if (typeof window.vlSwalError === 'function') {
                        window.vlSwalError(
                            'No se pudo restablecer la sesión. Cierre el navegador por completo e intente de nuevo.',
                            'Sesión expirada',
                        );
                    }
                    return;
                }

                sessionStorage.setItem(storageKey, String(ahora));
                vl419Recargando = true;

                const url = new URL(window.location.href);
                url.searchParams.set('_ses', String(ahora));
                window.location.replace(url.toString());
            });
        });
    }
});

document.addEventListener('alpine:init', () => {
    Alpine.data('vlRichTextEditor', (config = {}) => ({
        maxLength: Number(config.maxLength ?? 255),
        htmlLength: 0,
        colors: VL_EDITOR_COLORS,
        colorPickerOpen: false,
        placeholder: config.placeholder || 'Escriba el texto…',
        wireProperty: config.wireProperty || 'avisoTexto',
        saveMethod: config.saveMethod || null,

        init() {
            this.$nextTick(() => {
                const ed = this.$refs.editor;
                if (!ed) {
                    return;
                }
                ed.setAttribute('data-placeholder', this.placeholder);
                const inicial = String(config.initial || '').trim();
                if (inicial !== '') {
                    ed.innerHTML = inicial;
                }
                this.actualizarContador();
            });
        },

        actualizarContador() {
            const ed = this.$refs.editor;
            this.htmlLength = ed ? vlNormalizarHtmlEditor(ed.innerHTML).length : 0;
        },

        htmlActual() {
            const ed = this.$refs.editor;
            if (!ed) {
                return String(config.initial || '');
            }

            return vlNormalizarHtmlEditor(ed.innerHTML);
        },

        aplicar(comando, valor = null) {
            const ed = this.$refs.editor;
            if (!ed) {
                return;
            }
            ed.focus();
            document.execCommand(comando, false, valor);
            this.actualizarContador();
            this.colorPickerOpen = false;
        },

        async syncToLivewire() {
            await this.$wire.set(this.wireProperty, this.htmlActual());
        },

        async guardar() {
            await this.syncToLivewire();
            const method = this.saveMethod || 'guardarAviso';
            await this.$wire[method]();
        },
    }));

    /**
     * Combobox de tipodeterminaciones en carga de determinaciones del protocolo (F2).
     * Escribir filtra; flechas navegan; Enter elige y confirma; Esc cierra o cancela.
     */
    Alpine.data('vlProtDetCombobox', (config = {}) => ({
        opciones: Array.isArray(config.opciones) ? config.opciones : [],
        consulta: String(config.nombreInicial || ''),
        idSeleccionado: String(config.idInicial || ''),
        abierto: false,
        indice: -1,

        get filtrados() {
            const term = this.consulta.trim().toLowerCase();
            if (term === '') {
                return this.opciones;
            }

            return this.opciones.filter((o) => String(o.nombre || '').toLowerCase().includes(term));
        },

        init() {
            this.syncDataset();
        },

        syncDataset() {
            const el = this.$refs.input;
            if (!el) {
                return;
            }
            if (this.idSeleccionado) {
                el.dataset.selectedId = this.idSeleccionado;
            } else {
                delete el.dataset.selectedId;
            }
        },

        abrir() {
            this.abierto = true;
            this.$nextTick(() => {
                this.posicionarLista();
                this.scrollActivo();
            });
        },

        cerrar() {
            this.abierto = false;
            this.indice = -1;
        },

        onInput() {
            this.idSeleccionado = '';
            this.syncDataset();
            this.abierto = true;
            // Primera coincidencia lista para Enter; ↓/↑ cambian si no es la correcta.
            this.indice = this.filtrados.length > 0 ? 0 : -1;
            this.$nextTick(() => {
                this.posicionarLista();
                this.scrollActivo();
            });
        },

        posicionarLista() {
            const input = this.$refs.input;
            const lista = this.$refs.lista;
            if (!input || !lista || !this.abierto) {
                return;
            }
            const r = input.getBoundingClientRect();
            const ancho = Math.max(r.width, 220);
            lista.style.position = 'fixed';
            lista.style.left = `${Math.round(r.left)}px`;
            lista.style.top = `${Math.round(r.bottom + 2)}px`;
            lista.style.width = `${Math.round(ancho)}px`;
            lista.style.right = 'auto';
            lista.style.minWidth = '12rem';
        },

        onKeydown(event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                event.stopPropagation();
                if (!this.abierto) {
                    this.abrir();
                }
                if (this.filtrados.length === 0) {
                    return;
                }
                // Si aún no hay ítem activo, la primera ↓ entra a la lista en el primero.
                if (this.indice < 0) {
                    this.indice = 0;
                } else {
                    this.indice = this.indice < this.filtrados.length - 1 ? this.indice + 1 : 0;
                }
                this.$nextTick(() => {
                    this.posicionarLista();
                    this.scrollActivo();
                });
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                event.stopPropagation();
                if (!this.abierto) {
                    this.abrir();
                }
                if (this.filtrados.length === 0) {
                    return;
                }
                if (this.indice < 0) {
                    this.indice = this.filtrados.length - 1;
                } else {
                    this.indice = this.indice > 0 ? this.indice - 1 : this.filtrados.length - 1;
                }
                this.$nextTick(() => {
                    this.posicionarLista();
                    this.scrollActivo();
                });
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                event.stopPropagation();
                this.confirmarConTeclado();
                return;
            }

            if (event.key === 'Escape') {
                if (this.abierto) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.cerrar();
                }
            }
        },

        scrollActivo() {
            const lista = this.$refs.lista;
            if (!lista || this.indice < 0) {
                return;
            }
            const item = lista.querySelector(`[data-combo-idx="${this.indice}"]`);
            if (item) {
                item.scrollIntoView({ block: 'nearest' });
            }
        },

        async aplicarSeleccion(item, confirmar) {
            if (!item) {
                return;
            }
            this.consulta = String(item.nombre || '');
            this.idSeleccionado = String(item.id);
            this.syncDataset();
            this.cerrar();

            if (confirmar) {
                await this.$wire.confirmarNueva(String(item.id));
                return;
            }

            await this.$wire.set('filaNueva.idTipodeterminaciones', String(item.id));
        },

        async confirmarConTeclado() {
            if (this.abierto && this.indice >= 0 && this.filtrados[this.indice]) {
                await this.aplicarSeleccion(this.filtrados[this.indice], true);
                return;
            }

            if (this.idSeleccionado) {
                await this.$wire.confirmarNueva(this.idSeleccionado);
            }
        },

        async elegirClick(item) {
            await this.aplicarSeleccion(item, false);
        },
    }));

    /**
     * Combobox genérico de búsqueda para selects de formulario (ej. Cliente en alta de paciente).
     * Filtra en el cliente por la cadena completa; sincroniza con Livewire solo al confirmar.
     *
     * Config: { opciones: [{id, nombre}], idInicial, nombreInicial, propiedad }
     *   - propiedad: nombre de la propiedad Livewire a actualizar ($wire.set).
     */
    Alpine.data('vlSearchSelect', (config = {}) => ({
        opciones: Array.isArray(config.opciones) ? config.opciones : [],
        consulta: String(config.nombreInicial || ''),
        idSeleccionado: String(config.idInicial || ''),
        nombreConfirmado: String(config.nombreInicial || ''),
        propiedad: String(config.propiedad || ''),
        abierto: false,
        indice: -1,

        get filtrados() {
            const term = this.consulta.trim().toLowerCase();
            if (term === '') {
                return this.opciones;
            }
            return this.opciones.filter((o) => String(o.nombre || '').toLowerCase().includes(term));
        },

        abrir() {
            this.abierto = true;
            this.$nextTick(() => {
                this.posicionarLista();
                this.scrollActivo();
            });
        },

        cerrar() {
            this.abierto = false;
            this.indice = -1;
        },

        alSalir() {
            if (!this.abierto) {
                return;
            }
            this.cerrar();
            // Si cerró sin confirmar selección, restaura el texto anterior.
            this.consulta = this.nombreConfirmado;
        },

        onInput() {
            this.abierto = true;
            this.indice = this.filtrados.length > 0 ? 0 : -1;
            this.$nextTick(() => {
                this.posicionarLista();
                this.scrollActivo();
            });
        },

        posicionarLista() {
            const input = this.$refs.input;
            const lista = this.$refs.lista;
            if (!input || !lista || !this.abierto) {
                return;
            }
            const r = input.getBoundingClientRect();
            const ancho = Math.max(r.width, 220);
            lista.style.position = 'fixed';
            lista.style.left = `${Math.round(r.left)}px`;
            lista.style.top = `${Math.round(r.bottom + 2)}px`;
            lista.style.width = `${Math.round(ancho)}px`;
            lista.style.right = 'auto';
            lista.style.minWidth = '12rem';
        },

        onKeydown(event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                event.stopPropagation();
                if (!this.abierto) {
                    this.abrir();
                }
                if (this.filtrados.length === 0) {
                    return;
                }
                this.indice = this.indice < 0 ? 0
                    : (this.indice < this.filtrados.length - 1 ? this.indice + 1 : 0);
                this.$nextTick(() => { this.posicionarLista(); this.scrollActivo(); });
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                event.stopPropagation();
                if (!this.abierto) {
                    this.abrir();
                }
                if (this.filtrados.length === 0) {
                    return;
                }
                this.indice = this.indice < 0 ? this.filtrados.length - 1
                    : (this.indice > 0 ? this.indice - 1 : this.filtrados.length - 1);
                this.$nextTick(() => { this.posicionarLista(); this.scrollActivo(); });
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                event.stopPropagation();
                if (this.abierto && this.indice >= 0 && this.filtrados[this.indice]) {
                    this.elegir(this.filtrados[this.indice]);
                }
                return;
            }

            if (event.key === 'Tab') {
                if (this.abierto) {
                    this.cerrar();
                    this.consulta = this.nombreConfirmado;
                }
                return;
            }

            if (event.key === 'Escape') {
                if (this.abierto) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.cerrar();
                    this.consulta = this.nombreConfirmado;
                }
            }
        },

        scrollActivo() {
            const lista = this.$refs.lista;
            if (!lista || this.indice < 0) {
                return;
            }
            const item = lista.querySelector(`[data-combo-idx="${this.indice}"]`);
            if (item) {
                item.scrollIntoView({ block: 'nearest' });
            }
        },

        async elegir(item) {
            if (!item) {
                return;
            }
            this.consulta = String(item.nombre || '');
            this.nombreConfirmado = this.consulta;
            this.idSeleccionado = String(item.id);
            this.cerrar();
            const id = item.id !== '' ? Number(item.id) : null;
            await this.$wire.set(this.propiedad, id);
        },

        async limpiar() {
            this.consulta = '';
            this.nombreConfirmado = '';
            this.idSeleccionado = '';
            this.cerrar();
            await this.$wire.set(this.propiedad, null);
            this.$nextTick(() => this.$refs.input?.focus());
        },
    }));

    /**
     * Búsqueda de determinaciones en Estimación de costos.
     * ↑↓ navegan el listado Livewire; Enter agrega; Esc limpia la búsqueda.
     */
    Alpine.data('vlEstCostosBusqueda', () => ({
        indice: 0,

        idsDesdeDom() {
            const lista = this.$refs.lista;
            if (! lista) {
                return [];
            }

            return [...lista.querySelectorAll('[data-est-id]')].map((el) => Number(el.dataset.estId));
        },

        scrollActivo() {
            const lista = this.$refs.lista;
            if (! lista) {
                return;
            }
            const item = lista.querySelectorAll('[data-est-idx]')[this.indice];
            item?.scrollIntoView({ block: 'nearest' });
        },

        mover(delta) {
            const ids = this.idsDesdeDom();
            if (ids.length === 0) {
                this.indice = 0;

                return;
            }
            if (this.indice < 0 || this.indice >= ids.length) {
                this.indice = 0;
            } else {
                this.indice = (this.indice + delta + ids.length) % ids.length;
            }
            this.$nextTick(() => this.scrollActivo());
        },

        async elegirId(id) {
            if (! id) {
                return;
            }
            await this.$wire.agregarDeterminacion(id);
            this.indice = 0;
            this.$nextTick(() => this.$refs.buscar?.focus());
        },

        async elegirIndice() {
            const ids = this.idsDesdeDom();
            if (ids.length === 0) {
                return;
            }
            if (this.indice < 0 || this.indice >= ids.length) {
                this.indice = 0;
            }
            await this.elegirId(ids[this.indice]);
        },

        init() {
            this.$wire.$watch('busquedaDeterminacion', () => {
                this.indice = 0;
                this.$nextTick(() => this.scrollActivo());
            });
        },
    }));

    Alpine.data('vlCargaResultados', (config) => ({
        estadoPaciente: config.estadoInicial || 'En Proc.',

        init() {
            // Globales que usa el script legacy de entorno.formulas (ScriptCase).
            // Independientes del flag hemograma_auto.
            window.JSespecie = config.especieNombre || '';
            window.JSidEspecies = Number(config.idEspecies || 0);
            window.JSidSexos = Number(config.idSexos || 0);

            this.instalarFormulas(config.formulas || '');
            // Prepara runner formulas + serie roja/blanca (no envuelve window.formulas).
            instalarHemogramaAuto(config.hemogramaAuto || { activo: false });

            this.$nextTick(() => {
                const form = document.getElementById('vl-form-carga');
                if (!form) {
                    return;
                }

                const first = form.querySelector('input[type="text"]:not([readonly]):not([disabled]), textarea');
                if (first) {
                    first.focus();
                }
                // Al entrar: formulas() + estilos fuera de rango. Serie Roja/Blanca al editar.
                this.correrFormulas({ aplicarHemograma: false });
            });
        },

        /**
         * Llamado desde Blade (@change) y al guardar.
         * Ejecuta entorno.formulas; Serie Roja/Blanca solo si aplicarHemograma !== false
         * y el tenant lo tiene activo (no al arrancar el form).
         *
         * @param {{ aplicarHemograma?: boolean }} [opciones]
         */
        correrFormulas(opciones = {}) {
            if (typeof window.__vlCorrerFormulasYHemograma === 'function') {
                window.__vlCorrerFormulasYHemograma(opciones);
                return;
            }
            try {
                if (typeof window.formulas === 'function') {
                    window.formulas();
                }
            } catch (e) {
                console.error('[carga-resultados] formulas() error:', e);
            }
        },

        formatearYCalcular(idItems, estiloNum) {
            if (typeof window.formatearNumero === 'function') {
                window.formatearNumero(idItems, estiloNum);
            }
            this.correrFormulas();
        },

        formatearSolo(idItems, estiloNum) {
            if (typeof window.formatearNumero === 'function') {
                window.formatearNumero(idItems, estiloNum);
            }
        },

        reemplazarComa(idItems, estiloNum) {
            if (typeof window.reemplazarComaPorPunto === 'function') {
                window.reemplazarComaPorPunto(idItems, estiloNum);
            }
        },

        onSelectTipo4(idItems, idItems2) {
            if (typeof window.comportamientoSelect === 'function') {
                window.comportamientoSelect(idItems, idItems2);
            }
            // No correr formulas() (pisaría plaquetas); sí refrescar serie roja/blanca.
            if (typeof window.__vlAplicarHemogramaAuto === 'function') {
                window.__vlAplicarHemogramaAuto();
            }
        },

        onInputTipo4(idItems, idItems2) {
            if (typeof window.comportamientoInputSelect === 'function') {
                window.comportamientoInputSelect(idItems, idItems2);
            }
            if (typeof window.__vlAplicarHemogramaAuto === 'function') {
                window.__vlAplicarHemogramaAuto();
            }
        },

        instalarFormulas(codigo) {
            window.formulas = function () {};
            const texto = String(codigo || '').trim();
            if (!texto) {
                return;
            }

            const prev = document.getElementById('vl-formulas-runtime');
            if (prev) {
                prev.remove();
            }

            try {
                const script = document.createElement('script');
                script.id = 'vl-formulas-runtime';
                script.text = texto;
                document.body.appendChild(script);
                if (typeof window.formulas !== 'function') {
                    window.formulas = function () {};
                }
            } catch (e) {
                console.error('Error al cargar formulas()', e);
                window.formulas = function () {};
            }
        },

        camposNav() {
            const form = document.getElementById('vl-form-carga');
            if (!form) {
                return [];
            }

            return Array.from(form.querySelectorAll(
                'input[type="text"]:not([readonly]):not([disabled]), textarea:not([readonly]):not([disabled]), select:not([disabled])',
            ));
        },

        enfocarCampo(el) {
            if (!el) {
                return;
            }
            el.focus({ preventScroll: true });
            if (typeof el.select === 'function' && el.tagName !== 'SELECT') {
                try {
                    el.select();
                } catch {
                    // Algunos inputs no permiten select().
                }
            }
            el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        },

        todoSeleccionado(el) {
            if (el.tagName === 'SELECT') {
                return true;
            }
            const len = String(el.value ?? '').length;
            const start = el.selectionStart ?? 0;
            const end = el.selectionEnd ?? 0;
            return len === 0 || (start === 0 && end === len);
        },

        caretEnPrimeraLinea(el) {
            if (el.tagName !== 'TEXTAREA') {
                return true;
            }
            const pos = el.selectionStart ?? 0;
            return !String(el.value ?? '').slice(0, pos).includes('\n');
        },

        caretEnUltimaLinea(el) {
            if (el.tagName !== 'TEXTAREA') {
                return true;
            }
            const pos = el.selectionEnd ?? 0;
            return !String(el.value ?? '').slice(pos).includes('\n');
        },

        cambiarOpcionSelect(select, direccion) {
            const opciones = Array.from(select.options).filter((opt) => !opt.disabled);
            if (opciones.length === 0) {
                return;
            }

            let idx = opciones.findIndex((opt) => opt === select.options[select.selectedIndex]);
            if (idx < 0) {
                idx = direccion > 0 ? -1 : 0;
            }

            const nuevo = idx + direccion;
            if (nuevo < 0 || nuevo >= opciones.length) {
                return;
            }

            select.selectedIndex = opciones[nuevo].index;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        },

        navegarCampos(event) {
            const keys = ['Enter', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
            if (!keys.includes(event.key)) {
                return;
            }

            const actual = event.target;
            if (!actual) {
                return;
            }

            const form = document.getElementById('vl-form-carga');
            if (!form || !form.contains(actual)) {
                return;
            }

            const tag = actual.tagName;
            if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT') {
                return;
            }
            if (actual.readOnly || actual.disabled) {
                return;
            }
            if (tag === 'INPUT' && actual.type !== 'text') {
                return;
            }

            // En <select>: ←/→ cambian opción; ↑/↓ y Enter pasan de campo.
            if (tag === 'SELECT' && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
                event.preventDefault();
                this.cambiarOpcionSelect(actual, event.key === 'ArrowRight' ? 1 : -1);
                return;
            }

            // En textarea, Enter solo avanza; Shift+Enter inserta línea.
            if (tag === 'TEXTAREA' && event.key === 'Enter' && event.shiftKey) {
                return;
            }

            if (event.key === 'ArrowUp' && !this.caretEnPrimeraLinea(actual)) {
                return;
            }
            if (event.key === 'ArrowDown' && !this.caretEnUltimaLinea(actual)) {
                return;
            }

            const campos = this.camposNav();
            const idx = campos.indexOf(actual);
            if (idx < 0) {
                return;
            }

            let destino = null;

            if (event.key === 'Enter' || event.key === 'ArrowDown') {
                destino = campos[idx + 1] || null;
            } else if (event.key === 'ArrowUp') {
                destino = campos[idx - 1] || null;
            } else if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
                // ←/→ solo mueven el caret dentro del campo (nunca saltan).
                // Con todo el texto seleccionado (p. ej. al bajar del select),
                // colapsan el caret al margen para poder editar.
                if (this.todoSeleccionado(actual) && String(actual.value ?? '').length > 0) {
                    event.preventDefault();
                    const len = String(actual.value ?? '').length;
                    if (event.key === 'ArrowRight') {
                        actual.setSelectionRange(len, len);
                    } else {
                        actual.setSelectionRange(0, 0);
                    }
                }
                return;
            }

            if (!destino) {
                return;
            }

            event.preventDefault();
            this.enfocarCampo(destino);
        },

        onKeydown(event) {
            if (document.querySelector('.swal2-container')) {
                return;
            }

            const modalAa = document.getElementById('vl-modal-autoanalizador');
            if (modalAa) {
                this.onKeydownModalAutoanalizador(event, modalAa);
                return;
            }

            if (event.key === 'F9') {
                event.preventDefault();
                this.guardar(false);
                return;
            }
            if (event.key === 'F10') {
                event.preventDefault();
                this.guardar(true);
                return;
            }

            this.navegarCampos(event);
        },

        /**
         * Modal autoanalizadores: Tab trap + Enter/↑↓ entre campos + ←→ en selects.
         * El botón "Elegir archivo…" abre el file picker con Enter/Espacio (nativo).
         */
        onKeydownModalAutoanalizador(event, modal) {
            if (event.key === 'F9' || event.key === 'F10') {
                event.preventDefault();
                return;
            }

            const focusables = this.camposModalAutoanalizador(modal);
            if (focusables.length === 0) {
                return;
            }

            if (event.key === 'Tab') {
                const first = focusables[0];
                const last = focusables[focusables.length - 1];
                const actual = document.activeElement;
                if (!modal.contains(actual)) {
                    event.preventDefault();
                    (event.shiftKey ? last : first).focus();
                    return;
                }
                if (event.shiftKey && actual === first) {
                    event.preventDefault();
                    last.focus();
                    return;
                }
                if (!event.shiftKey && actual === last) {
                    event.preventDefault();
                    first.focus();
                }
                return;
            }

            const keysNav = ['Enter', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
            if (!keysNav.includes(event.key)) {
                return;
            }

            let actual = document.activeElement;
            if (!actual || !modal.contains(actual)) {
                event.preventDefault();
                focusables[0].focus();
                return;
            }

            // Solo tratamos controles del modal (select / botones con data-vl-aa-campo).
            const campo = actual.getAttribute?.('data-vl-aa-campo');
            if (!campo) {
                return;
            }

            if (actual.tagName === 'SELECT' && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
                event.preventDefault();
                this.cambiarOpcionSelect(actual, event.key === 'ArrowRight' ? 1 : -1);
                return;
            }

            // Enter / Espacio en botones: comportamiento nativo (abre file picker o dispara acción).
            if (actual.tagName === 'BUTTON' && (event.key === 'Enter' || event.key === ' ')) {
                return;
            }

            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                return;
            }

            // Cadena Enter/↑↓: upload → aparato → archivo → importar (salta Cancelar).
            const cadena = focusables.filter((el) => {
                const c = el.getAttribute('data-vl-aa-campo');
                return c === 'upload' || c === 'aparato' || c === 'archivo' || c === 'importar';
            });
            const idx = cadena.indexOf(actual);
            if (idx < 0) {
                // En Cancelar: ↑ vuelve a archivo; ↓/Enter a importar.
                if (campo === 'cancelar') {
                    event.preventDefault();
                    if (event.key === 'ArrowUp') {
                        document.getElementById('vl-aa-archivo')?.focus();
                    } else {
                        document.getElementById('vl-aa-importar')?.focus();
                    }
                }
                return;
            }

            let destino = null;
            if (event.key === 'Enter' || event.key === 'ArrowDown') {
                destino = cadena[idx + 1] || null;
            } else if (event.key === 'ArrowUp') {
                destino = cadena[idx - 1] || null;
            }

            if (!destino) {
                return;
            }

            event.preventDefault();
            destino.focus();
        },

        camposModalAutoanalizador(modal) {
            return Array.from(modal.querySelectorAll('[data-vl-aa-campo]')).filter((el) => {
                if (el.disabled) {
                    return false;
                }
                // Visible (botones/selects del diálogo).
                return el.offsetParent !== null || el.getClientRects().length > 0;
            });
        },

        enfocarAutoanalizador(target) {
            const map = {
                upload: 'vl-aa-upload-btn',
                aparato: 'vl-aa-aparato',
                archivo: 'vl-aa-archivo',
                importar: 'vl-aa-importar',
                cancelar: 'vl-aa-cancelar',
            };
            const id = map[target] || map.upload;

            const intentar = (intentos) => {
                if (document.querySelector('.swal2-container')) {
                    if (intentos > 0) {
                        setTimeout(() => intentar(intentos - 1), 80);
                    }
                    return;
                }
                const el = document.getElementById(id);
                if (el) {
                    el.focus();
                    return;
                }
                if (intentos > 0) {
                    setTimeout(() => intentar(intentos - 1), 40);
                }
            };

            this.$nextTick(() => intentar(25));
        },

        recolectarPayload() {
            const form = document.getElementById('vl-form-carga');
            const valores = {};
            const valores2 = {};
            if (!form) {
                return { valores, valores2 };
            }

            form.querySelectorAll('[data-renglon][data-campo]').forEach((el) => {
                const id = String(el.getAttribute('data-renglon') || '');
                const campo = el.getAttribute('data-campo');
                if (!id || !campo) {
                    return;
                }
                const valor = el.value ?? '';
                if (campo === 'valor') {
                    valores[id] = valor;
                } else if (campo === 'valor2') {
                    valores2[id] = valor;
                }
            });

            return { valores, valores2 };
        },

        async guardar(salir) {
            // formulas sí; hemograma no (ya se aplicó al editar orígenes si correspondía).
            this.correrFormulas({ aplicarHemograma: false });
            const { valores, valores2 } = this.recolectarPayload();
            await this.$wire.guardar(valores, valores2, this.estadoPaciente, !!salir);
        },
    }));

    /**
     * Marco adaptativo del logo (login / sidebar).
     * Login: tope 70 % × 60 % de la tarjeta del formulario (usuario/clave), sin deformar.
     * Sidebar: wide/square/tall; recorte si el PNG es cuadrado con marca horizontal.
     */
    Alpine.data('vlAuthLogoFrame', (config = {}) => ({
        shape: config.shape || 'square',
        cropped: false,
        contentAr: null,
        viewBox: null,
        logoW: null,
        logoH: null,
        fitted: false,
        variant: config.variant || 'login',
        _onResize: null,

        init() {
            this.$nextTick(() => {
                const img = this.$el.querySelector('img');
                if (img && img.complete && img.naturalWidth > 0) {
                    this.onLoad({ target: img });
                }
            });

            if (this.variant === 'login') {
                this._onResize = () => {
                    const img = this.$el.querySelector('img');
                    if (img && img.naturalWidth > 0) {
                        this.fitLoginSize(img);
                    }
                };
                window.addEventListener('resize', this._onResize);
                this.$nextTick(() => {
                    const card = this.loginCardEl();
                    if (card && typeof ResizeObserver !== 'undefined') {
                        this._cardRo = new ResizeObserver(() => this._onResize());
                        this._cardRo.observe(card);
                    }
                });
            }
        },

        loginCardEl() {
            const stack = this.$el.closest('.vl-auth-page__stack');

            return stack?.querySelector('.vl-auth-card--login')
                || document.querySelector('.vl-auth-card--login');
        },

        get frameClass() {
            if (this.variant === 'login') {
                return {
                    'vl-auth-logo-frame--cropped': this.cropped,
                    'vl-auth-logo-frame--fitted': this.fitted,
                };
            }

            const prefix = 'vl-sidebar-brand__mark';

            return {
                [`${prefix}--wide`]: this.shape === 'wide',
                [`${prefix}--square`]: this.shape === 'square',
                [`${prefix}--tall`]: this.shape === 'tall',
                [`${prefix}--cropped`]: this.cropped,
            };
        },

        get frameStyle() {
            const style = {};
            if (this.viewBox) {
                style['--vl-logo-view-box'] = this.viewBox;
            }
            if (this.contentAr && this.variant === 'sidebar' && this.shape === 'tall') {
                style['--vl-logo-ar'] = this.contentAr;
            }
            if (this.fitted && this.logoW && this.logoH) {
                style['--vl-logo-w'] = this.logoW;
                style['--vl-logo-h'] = this.logoH;
            }

            return style;
        },

        supportsObjectViewBox() {
            return typeof CSS !== 'undefined'
                && typeof CSS.supports === 'function'
                && CSS.supports('object-view-box', 'inset(10%)');
        },

        contentAspectRatio(img) {
            if (this.contentAr) {
                const match = String(this.contentAr).match(/([\d.]+)\s*\/\s*([\d.]+)/);
                if (match && parseFloat(match[2]) > 0) {
                    return parseFloat(match[1]) / parseFloat(match[2]);
                }
            }

            return img.naturalWidth / img.naturalHeight;
        },

        fitLoginSize(img, attempt = 0) {
            if (this.variant !== 'login' || !img) {
                return;
            }

            const card = this.loginCardEl();
            const slot = this.$el.closest('.vl-auth-logo-slot');
            const refW = card?.clientWidth
                || slot?.clientWidth
                || this.$el.parentElement?.clientWidth
                || 320;
            const refH = card?.clientHeight || 0;

            // La tarjeta del form es la referencia del 60 %; si aún no midió, reintentar.
            if (refH < 32 && attempt < 12) {
                requestAnimationFrame(() => this.fitLoginSize(img, attempt + 1));

                return;
            }

            // Regla: ≤ 70 % del ancho y ≤ 60 % del alto de `.vl-auth-card--login`.
            const maxW = Math.max(48, refW * 0.7);
            const maxH = Math.max(48, (refH > 0 ? refH : 220) * 0.6);
            const ar = this.contentAspectRatio(img);

            let width = maxW;
            let height = width / ar;
            if (height > maxH) {
                height = maxH;
                width = height * ar;
            }

            this.logoW = `${Math.round(width * 100) / 100}px`;
            this.logoH = `${Math.round(height * 100) / 100}px`;
            this.fitted = true;
        },

        onLoad(event) {
            const img = event.target;
            if (!img || !img.naturalWidth || !img.naturalHeight) {
                return;
            }

            const ratio = img.naturalWidth / img.naturalHeight;
            let shape = ratio >= 1.2 ? 'wide' : (ratio <= 0.75 ? 'tall' : 'square');
            let cropped = false;
            let contentAr = null;
            let viewBox = null;
            const bbox = this.contentBBox(img);
            const canCrop = this.supportsObjectViewBox();

            if (shape === 'square' && bbox && bbox.h > 0) {
                const contentRatio = bbox.w / bbox.h;
                if (contentRatio >= 1.25) {
                    shape = 'wide';
                    if (canCrop) {
                        cropped = true;
                        contentAr = `${bbox.w} / ${bbox.h}`;
                        viewBox = this.insetFromBBox(bbox, img.naturalWidth, img.naturalHeight);
                    }
                } else if (contentRatio <= 0.7) {
                    shape = 'tall';
                    if (canCrop) {
                        cropped = true;
                        contentAr = `${bbox.w} / ${bbox.h}`;
                        viewBox = this.insetFromBBox(bbox, img.naturalWidth, img.naturalHeight);
                    }
                }
            }

            this.shape = shape;
            this.cropped = cropped;
            this.contentAr = contentAr;
            this.viewBox = viewBox;

            if (this.variant === 'login') {
                this.$nextTick(() => this.fitLoginSize(img));
            }
        },

        insetFromBBox(bbox, naturalW, naturalH) {
            // Holgura para no comer antialiasing del trazo / tipografía.
            const padX = bbox.w * 0.04;
            const padY = bbox.h * 0.06;
            const x = Math.max(0, bbox.x - padX);
            const y = Math.max(0, bbox.y - padY);
            const right = Math.max(0, naturalW - (bbox.x + bbox.w) - padX);
            const bottom = Math.max(0, naturalH - (bbox.y + bbox.h) - padY);
            const topPct = (y / naturalH) * 100;
            const rightPct = (right / naturalW) * 100;
            const bottomPct = (bottom / naturalH) * 100;
            const leftPct = (x / naturalW) * 100;

            return `inset(${topPct}% ${rightPct}% ${bottomPct}% ${leftPct}%)`;
        },

        contentBBox(img) {
            try {
                const maxScan = 360;
                const scale = Math.min(1, maxScan / Math.max(img.naturalWidth, img.naturalHeight));
                const w = Math.max(1, Math.round(img.naturalWidth * scale));
                const h = Math.max(1, Math.round(img.naturalHeight * scale));
                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                if (!ctx) {
                    return null;
                }
                ctx.drawImage(img, 0, 0, w, h);
                const { data } = ctx.getImageData(0, 0, w, h);
                let minX = w;
                let minY = h;
                let maxX = 0;
                let maxY = 0;
                let found = false;

                for (let y = 0; y < h; y += 1) {
                    for (let x = 0; x < w; x += 1) {
                        const i = (y * w + x) * 4;
                        const a = data[i + 3];
                        const r = data[i];
                        const g = data[i + 1];
                        const b = data[i + 2];
                        const nearWhite = r > 248 && g > 248 && b > 248;
                        if (a < 18 || nearWhite) {
                            continue;
                        }
                        found = true;
                        if (x < minX) minX = x;
                        if (y < minY) minY = y;
                        if (x > maxX) maxX = x;
                        if (y > maxY) maxY = y;
                    }
                }

                if (!found) {
                    return null;
                }

                const sx = img.naturalWidth / w;
                const sy = img.naturalHeight / h;

                return {
                    x: minX * sx,
                    y: minY * sy,
                    w: (maxX - minX + 1) * sx,
                    h: (maxY - minY + 1) * sy,
                };
            } catch {
                return null;
            }
        },
    }));
});

window.comportamientoSelect = function (idItems, idItems2) {
    const textbox = document.getElementById(idItems);
    const select = document.getElementById(idItems2);
    if (!textbox || !select) {
        return;
    }
    textbox.value = select.value;
};

window.comportamientoInputSelect = function (idItems, idItems2) {
    const textbox = document.getElementById(idItems);
    const select = document.getElementById(idItems2);
    if (!textbox || !select) {
        return;
    }
    if (textbox.value === '0' || textbox.value === '') {
        select.value = '';
    }
};

window.reemplazarComaPorPunto = function (idItems, estiloNum) {
    const input = document.getElementById(String(idItems));
    if (!input) {
        return;
    }
    if (estiloNum === 1) {
        input.value = input.value.replace(/[.,]/g, '');
    } else if (estiloNum === 2 || estiloNum === 3) {
        input.value = input.value.replace(',', '.');
    }
};

window.formatearNumero = function (idItems, estiloNum) {
    const input = document.getElementById(String(idItems));
    if (!input) {
        return;
    }
    const valor = input.value;
    if (estiloNum === 1) {
        input.value = window.formatoConPuntosYComa(valor);
    } else if (estiloNum === 2) {
        input.value = window.formatoDecimal(valor, 1);
    } else if (estiloNum === 3) {
        input.value = window.formatoDecimal(valor, 2);
    }
};

window.formatoConPuntosYComa = function (valor) {
    const limpio = String(valor).replace(/\./g, '');
    if (limpio === '' || Number.isNaN(Number(limpio.charAt(0)))) {
        return String(valor);
    }

    return limpio.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

window.formatoDecimal = function (valor, decimales) {
    const str = String(valor);
    if (str === '') {
        return '';
    }
    if (Number.isNaN(Number(str.charAt(0)))) {
        return str;
    }
    const n = parseFloat(str);
    if (Number.isNaN(n)) {
        return str;
    }

    return n.toFixed(decimales);
};
