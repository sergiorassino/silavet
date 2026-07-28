/**
 * Automatización Serie Roja / Serie Blanca (hemograma por rangos).
 * Activa solo si el tenant envía hemogramaAuto.activo + mapa de idItems.
 *
 * No reemplaza entorno.formulas: expone un runner que (1) ejecuta formulas(),
 * (2) preserva plaquetas si el cálculo quedó vacío sin conteo manual, (3) aplica
 * el texto automático. vlCargaResultados llama a ese runner; window.formulas
 * queda como el script de entorno sin envolver.
 */
export function instalarHemogramaAuto(config) {
    window.__vlCorrerFormulasYHemograma = function correrSoloFormulas() {
        if (typeof window.formulas === 'function') {
            window.formulas();
        }
    };

    if (!config || !config.activo || !config.items) {
        window.__vlAplicarHemogramaAuto = function () {};
        return;
    }

    const items = config.items;
    const rangos = Array.isArray(config.rangos) ? config.rangos : [];
    const idEspecies = Number(config.idEspecies || 0);
    const idSexos = Number(config.idSexos || 0);
    const idPlaquetas = items.plaquetas || null;
    const idConteoManual = items.plaquetas_conteo_manual || null;

    function parsearValorNumerico(valorStr) {
        if (!valorStr || valorStr === '') return NaN;
        let limpio = String(valorStr).trim();
        if (limpio === '' || (isNaN(limpio.charAt(0)) && limpio.charAt(0) !== '-')) return NaN;

        if (limpio.indexOf(',') !== -1) {
            limpio = limpio.replace(/\./g, '').replace(',', '.');
        } else {
            const cantPuntos = (limpio.match(/\./g) || []).length;
            if (cantPuntos > 1) {
                limpio = limpio.replace(/\./g, '');
            } else if (cantPuntos === 1) {
                const partes = limpio.split('.');
                const decimales = partes[1] || '';
                // "1.234" estilo miles (3 decimales) → 1234; "37.5" → decimal
                if (decimales.length === 3) {
                    limpio = partes[0] + decimales;
                }
            }
        }
        return parseFloat(limpio);
    }

    function esVacioOPendiente(texto) {
        const t = String(texto || '').trim();
        return t === '' || /^PENDIENTE$/i.test(t) || t === 'NaN';
    }

    function obtenerRangoValores(idItems) {
        let fallbackEspecie = null;
        for (let i = 0; i < rangos.length; i++) {
            const r = rangos[i];
            if (r.idItems != idItems || r.idEspecies != idEspecies) {
                continue;
            }
            // Match exacto especie + sexo.
            if (idSexos && r.idSexos == idSexos) {
                return r;
            }
            if (fallbackEspecie === null) {
                fallbackEspecie = r;
            }
        }
        // Paciente sin sexo (idSexos=0): usar cualquier rango de esa especie
        // (en la práctica suelen ser iguales para los 4 sexos).
        if (!idSexos) {
            return fallbackEspecie;
        }
        return null;
    }

    function escaparRegex(texto) {
        return String(texto).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function clasificarPorRango(valor, valorMin, valorMax) {
        if (isNaN(valor)) return '';
        if (valor < valorMin) return 'bajo';
        if (valor > valorMax) return 'alto';
        return 'normal';
    }

    /**
     * Un valor: campo idItems.
     * Diferencial (dos valores): absoluto en idItems_2 / idItems_T (rangovalores
     * del lab están en absolutos; formulas() calcula esa 2.ª columna).
     */
    function obtenerValorItem(idOrigen, dosValores) {
        let campo = null;
        if (dosValores) {
            campo = document.getElementById(String(idOrigen) + '_2')
                || document.getElementById(String(idOrigen) + '_T');
        } else {
            campo = document.getElementById(String(idOrigen));
        }
        if (!campo) return NaN;

        const texto = String(campo.value || '').trim();
        if (esVacioOPendiente(texto)) return NaN;

        if (!dosValores && idPlaquetas && idOrigen == idPlaquetas) {
            const entreParentesis = texto.match(/\(([\d.,]+)\)/);
            if (entreParentesis) {
                return parsearValorNumerico(entreParentesis[1]);
            }
            return parsearValorNumerico(texto);
        }

        return parsearValorNumerico(texto);
    }

    function evaluarItem(idOrigen, dosValores) {
        if (!idOrigen) return { clase: '', valor: NaN };
        const rango = obtenerRangoValores(idOrigen);
        if (!rango) return { clase: '', valor: NaN };
        const valor = obtenerValorItem(idOrigen, !!dosValores);
        return {
            clase: clasificarPorRango(valor, parseFloat(rango.valorMin), parseFloat(rango.valorMax)),
            valor,
        };
    }

    function textoClasificacion(clase, textoBajo, textoNormal, textoAlto) {
        if (clase === 'bajo') return textoBajo || '';
        if (clase === 'alto') return textoAlto || '';
        if (clase === 'normal') return textoNormal || '';
        return '';
    }

    function listarFrasesAutomaticas() {
        return [
            'Anemia', 'Policitemia', 'Normal',
            'microcítica', 'normocítica', 'macrócitica',
            'hipocrómica', 'normocrómica', 'hipercrómica',
            'Trombocitopenia', 'Trombocitosis',
            'Leucopenia', 'Leucocitosis',
            'Neutropenia', 'Neutrofilia',
            'Desvío a la izquierda regenerativo',
            'Linfopenia', 'Linfocitosis',
            'Eosinopenia', 'Eosinofilia',
            'Basofília',
            'Monocitopenia', 'Monocitosis',
        ];
    }

    function extraerTextoManual(texto) {
        texto = String(texto || '');
        texto = texto.replace(/\{\{AUTO:\d+\}\}[\s\S]*?\{\{\/AUTO:\d+\}\}\.?/g, '');
        texto = texto.replace(/\bPENDIENTE\b/gi, ' ');
        const frases = listarFrasesAutomaticas();
        for (let i = 0; i < frases.length; i++) {
            texto = texto.replace(new RegExp(escaparRegex(frases[i]), 'gi'), ' ');
        }
        texto = texto.replace(/\s*\.\s*\./g, '.').replace(/^\s*\.\s*/, '').replace(/\s*\.\s*$/, '').replace(/\s+/g, ' ').trim();
        return texto;
    }

    function combinarManualYAutomatico(textoManual, textoAuto) {
        textoManual = (textoManual || '').trim();
        textoAuto = (textoAuto || '').trim();
        if (!textoManual) return textoAuto;
        if (!textoAuto || /^normal\.?$/i.test(textoAuto)) return textoManual;
        return (textoManual.replace(/\.$/, '') + '. ' + textoAuto).replace(/\s+/g, ' ').trim();
    }

    function armarSerieRoja() {
        const hto = evaluarItem(items.hto, false);
        const erit = evaluarItem(items.eritrocitos, false);
        const hb = evaluarItem(items.hb, false);
        const vcm = evaluarItem(items.vcm, false);
        const chcm = evaluarItem(items.chcm, false);
        const plt = evaluarItem(items.plaquetas, false);

        const textoVcm = textoClasificacion(vcm.clase, 'microcítica', 'normocítica', 'macrócitica');
        const textoChcm = textoClasificacion(chcm.clase, 'hipocrómica', 'normocrómica', 'hipercrómica');
        const textoPlt = textoClasificacion(plt.clase, 'Trombocitopenia.', '', 'Trombocitosis.');

        let base = '';
        if (hto.clase === 'bajo') {
            if (erit.clase === 'bajo' && hb.clase === 'bajo') {
                base = ('Anemia ' + textoVcm + ' ' + textoChcm + '.').replace(/\s+/g, ' ').trim();
            } else {
                base = 'Anemia.';
            }
        } else if (hto.clase === 'alto') {
            base = 'Policitemia';
        } else if (hto.clase === 'normal') {
            base = 'Normal.';
        }

        if (!base) return '';
        return (base + (textoPlt ? ' ' + textoPlt : '')).replace(/\s+/g, ' ').trim();
    }

    function armarSerieBlanca() {
        const reglas = [
            { rol: 'leucocitos', dosValores: false, textoBajo: 'Leucopenia. ', textoAlto: 'Leucocitosis. ' },
            { rol: 'neutrofilos', dosValores: true, textoBajo: 'Neutropenia. ', textoAlto: 'Neutrofilia. ' },
            { rol: 'bandas', dosValores: true, textoBajo: '', textoAlto: 'Desvío a la izquierda regenerativo. ' },
            { rol: 'linfocitos', dosValores: true, textoBajo: 'Linfopenia. ', textoAlto: 'Linfocitosis. ' },
            { rol: 'eosinofilos', dosValores: true, textoBajo: 'Eosinopenia. ', textoAlto: 'Eosinofilia. ' },
            { rol: 'basofilos', dosValores: true, textoBajo: '', textoAlto: 'Basofília. ' },
            { rol: 'monocitos', dosValores: true, textoBajo: 'Monocitopenia. ', textoAlto: 'Monocitosis. ' },
        ];

        let concatenado = '';
        let huboClasificacion = false;
        for (let i = 0; i < reglas.length; i++) {
            const regla = reglas[i];
            const idOrigen = items[regla.rol];
            if (!idOrigen) continue;
            const ev = evaluarItem(idOrigen, regla.dosValores);
            if (ev.clase) {
                huboClasificacion = true;
            }
            concatenado += textoClasificacion(ev.clase, regla.textoBajo, '', regla.textoAlto);
        }

        // Sin ningún valor numérico usable: no tocar el destino (evita borrar PENDIENTE).
        if (!huboClasificacion) return '';

        concatenado = concatenado.replace(/\s+/g, ' ').trim();
        if (!concatenado) return 'Normal.';
        return concatenado;
    }

    function escribirDestino(idDestino, textoAuto) {
        if (!idDestino) return;
        // Sin texto automático: no modificar el campo (preserva PENDIENTE / manual).
        if (!textoAuto) return;

        const campo = document.getElementById(String(idDestino));
        if (!campo) return;

        campo.value = combinarManualYAutomatico(
            extraerTextoManual(campo.value),
            textoAuto,
        );
    }

    function aplicarAutomatizacionesRango() {
        if (!idEspecies) return;
        escribirDestino(items.serie_roja, armarSerieRoja());
        escribirDestino(items.serie_blanca, armarSerieBlanca());
    }

    window.__vlAplicarHemogramaAuto = function () {
        try {
            aplicarAutomatizacionesRango();
        } catch (e) {
            console.error('[hemograma-auto] error:', e);
        }
    };

    function valorConteoManualUtilizable() {
        if (!idConteoManual) return false;
        const campo = document.getElementById(String(idConteoManual));
        if (!campo) return false;
        if (esVacioOPendiente(campo.value)) return false;
        return !isNaN(parsearValorNumerico(campo.value));
    }

    function preservarPlaquetasSiCalculoVacio(valorPrevio) {
        if (!idPlaquetas || valorPrevio === null) return;
        if (valorConteoManualUtilizable()) return;

        const campo = document.getElementById(String(idPlaquetas));
        if (!campo) return;
        if (esVacioOPendiente(campo.value)) {
            campo.value = valorPrevio;
        }
    }

    window.__vlCorrerFormulasYHemograma = function correrFormulasYHemograma() {
        const campoPlt = idPlaquetas ? document.getElementById(String(idPlaquetas)) : null;
        const plaquetasPrevias = campoPlt ? campoPlt.value : null;

        try {
            if (typeof window.formulas === 'function') {
                window.formulas();
            }
        } catch (e) {
            console.error('[carga-resultados] formulas() error:', e);
        }

        preservarPlaquetasSiCalculoVacio(plaquetasPrevias);

        window.__vlAplicarHemogramaAuto();
    };
}
