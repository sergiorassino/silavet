import './bootstrap';
import Swal from 'sweetalert2';
import './cantidad-determinaciones-comparac';

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

    Alpine.data('vlCargaResultados', (config) => ({
        estadoPaciente: config.estadoInicial || 'En Proc.',

        init() {
            this.instalarFormulas(config.formulas || '');
            this.$nextTick(() => {
                const form = document.getElementById('vl-form-carga');
                if (!form) {
                    return;
                }
                const first = form.querySelector('input[type="text"]:not([readonly]):not([disabled]), textarea');
                if (first) {
                    first.focus();
                }
                if (typeof window.formulas === 'function') {
                    window.formulas();
                }
            });
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

        caretAlInicio(el) {
            if (el.tagName === 'SELECT') {
                return true;
            }
            const start = el.selectionStart ?? 0;
            const end = el.selectionEnd ?? 0;
            return start === 0 && end === 0;
        },

        caretAlFinal(el) {
            if (el.tagName === 'SELECT') {
                return true;
            }
            const len = String(el.value ?? '').length;
            const start = el.selectionStart ?? 0;
            const end = el.selectionEnd ?? 0;
            return start === len && end === len;
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
            } else if (event.key === 'ArrowRight') {
                if (!this.todoSeleccionado(actual) && !this.caretAlFinal(actual)) {
                    return;
                }
                destino = campos[idx + 1] || null;
            } else if (event.key === 'ArrowLeft') {
                if (!this.todoSeleccionado(actual) && !this.caretAlInicio(actual)) {
                    return;
                }
                destino = campos[idx - 1] || null;
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

            const modalAa = document.getElementById('modal-autoanalizador-titulo');
            if (modalAa && modalAa.closest('[role="dialog"]')?.contains(document.activeElement)) {
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
            if (typeof window.formulas === 'function') {
                window.formulas();
            }
            const { valores, valores2 } = this.recolectarPayload();
            await this.$wire.guardar(valores, valores2, this.estadoPaciente, !!salir);
        },
    }));

    /**
     * Marco adaptativo del logo (login / sidebar): wide/square/tall y recorte
     * si el PNG es cuadrado con marca horizontal (márgenes blancos).
     */
    Alpine.data('vlAuthLogoFrame', (config = {}) => ({
        shape: config.shape || 'square',
        cropped: false,
        dense: false,
        contentAr: null,
        viewBox: null,
        variant: config.variant || 'login',

        init() {
            this.$nextTick(() => {
                const img = this.$el.querySelector('img');
                if (img && img.complete && img.naturalWidth > 0) {
                    this.onLoad({ target: img });
                }
            });
        },

        get frameClass() {
            const prefix = this.variant === 'sidebar'
                ? 'vl-sidebar-brand__mark'
                : 'vl-auth-logo-frame';

            return {
                [`${prefix}--wide`]: this.shape === 'wide',
                [`${prefix}--square`]: this.shape === 'square',
                [`${prefix}--tall`]: this.shape === 'tall',
                [`${prefix}--cropped`]: this.cropped,
                [`${prefix}--dense`]: this.dense,
            };
        },

        get frameStyle() {
            if (!this.cropped || !this.contentAr) {
                return {};
            }

            const style = { '--vl-logo-ar': this.contentAr };
            if (this.viewBox) {
                style['--vl-logo-view-box'] = this.viewBox;
            }

            return style;
        },

        supportsObjectViewBox() {
            return typeof CSS !== 'undefined'
                && typeof CSS.supports === 'function'
                && CSS.supports('object-view-box', 'inset(10%)');
        },

        onLoad(event) {
            const img = event.target;
            if (!img || !img.naturalWidth || !img.naturalHeight) {
                return;
            }

            const ratio = img.naturalWidth / img.naturalHeight;
            let shape = ratio >= 1.2 ? 'wide' : (ratio <= 0.75 ? 'tall' : 'square');
            let cropped = false;
            let dense = false;
            let contentAr = null;
            let viewBox = null;
            const bbox = this.contentBBox(img);
            const canCrop = this.supportsObjectViewBox();

            if (shape === 'square' && bbox && bbox.h > 0) {
                const contentRatio = bbox.w / bbox.h;
                if (contentRatio >= 1.25) {
                    shape = 'wide';
                    // object-fit:cover sobre el JPG cuadrado corta la marca;
                    // object-view-box recorta solo el margen blanco.
                    if (canCrop) {
                        cropped = true;
                        contentAr = `${bbox.w} / ${bbox.h}`;
                        viewBox = this.insetFromBBox(bbox, img.naturalWidth, img.naturalHeight);
                    } else {
                        dense = true;
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

            if (shape === 'wide') {
                if (cropped) {
                    dense = true;
                } else if (ratio < 2.8) {
                    dense = true;
                } else if (bbox && bbox.h > 0) {
                    const contentRatio = bbox.w / bbox.h;
                    const heightFill = bbox.h / img.naturalHeight;
                    if (contentRatio < 3.2 || heightFill > 0.5) {
                        dense = true;
                    }
                }
            }

            this.shape = shape;
            this.cropped = cropped;
            this.dense = dense;
            this.contentAr = contentAr;
            this.viewBox = viewBox;
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
