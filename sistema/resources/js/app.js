import './bootstrap';
import Swal from 'sweetalert2';

window.Swal = Swal;

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
});

document.addEventListener('alpine:init', () => {
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

        onKeydown(event) {
            if (event.key === 'F9') {
                event.preventDefault();
                this.guardar(false);
            }
            if (event.key === 'F10') {
                event.preventDefault();
                this.guardar(true);
            }
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
