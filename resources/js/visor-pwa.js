/**
 * Visor in-app para PWA instalada en celular.
 *
 * En display: standalone, target=_blank / window.open de una URL del mismo
 * origen abre otra ventana de la app (con cruz nativa). Cerrar esa ventana
 * cierra también el sistema. Aquí el PDF/documento se muestra en un overlay
 * propio; la cruz solo oculta el visor.
 */

let visorEl = null;
let visorBlobUrl = null;
let visorAbort = null;
let visorHistoryPushed = false;
let visorNombreDescarga = 'documento.pdf';
let visorAbierto = false;

function vlEsPwaStandalone() {
    if (window.matchMedia('(display-mode: standalone)').matches) {
        return true;
    }
    if (window.matchMedia('(display-mode: fullscreen)').matches) {
        return true;
    }
    if (window.matchMedia('(display-mode: minimal-ui)').matches) {
        return true;
    }

    return window.navigator.standalone === true;
}

function vlEsDispositivoMovil() {
    const ua = navigator.userAgent || '';
    const iPadOs = navigator.platform === 'MacIntel' && (navigator.maxTouchPoints || 0) > 1;

    return /Android|iPhone|iPad|iPod/i.test(ua) || iPadOs;
}

function vlUrlMismaOrigen(url) {
    try {
        return new URL(url, window.location.href).origin === window.location.origin;
    } catch {
        return false;
    }
}

export function vlDebeVisorInApp(url) {
    return vlEsPwaStandalone() && vlEsDispositivoMovil() && vlUrlMismaOrigen(url);
}

function vlNombreDesdeDisposition(header) {
    if (!header) {
        return 'documento.pdf';
    }
    const star = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (star) {
        try {
            return decodeURIComponent(star[1].trim()) || 'documento.pdf';
        } catch {
            // seguir con filename= simple
        }
    }
    const basic = header.match(/filename="?([^";]+)"?/i);
    if (basic) {
        return basic[1].trim() || 'documento.pdf';
    }

    return 'documento.pdf';
}

function vlTituloDesdeUrl(url) {
    try {
        const path = new URL(url, window.location.href).pathname.toLowerCase();
        if (path.includes('informe')) {
            return 'Informe';
        }
        if (path.includes('etiqueta')) {
            return 'Etiquetas';
        }

        return 'Documento';
    } catch {
        return 'Documento';
    }
}

function vlLimpiarBlob() {
    if (visorBlobUrl) {
        URL.revokeObjectURL(visorBlobUrl);
        visorBlobUrl = null;
    }
}

function vlAsegurarVisor() {
    if (visorEl) {
        return visorEl;
    }

    const root = document.createElement('div');
    root.id = 'vl-visor-pwa';
    root.className = 'vl-visor-pwa';
    root.setAttribute('hidden', '');
    root.setAttribute('aria-hidden', 'true');
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-labelledby', 'vl-visor-pwa-titulo');
    root.innerHTML = `
        <div class="vl-visor-pwa__barra">
            <button type="button" class="vl-visor-pwa__btn vl-visor-pwa__cerrar" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <span id="vl-visor-pwa-titulo" class="vl-visor-pwa__titulo">Documento</span>
            <button type="button" class="vl-visor-pwa__btn vl-visor-pwa__descargar" aria-label="Descargar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </button>
        </div>
        <div class="vl-visor-pwa__cuerpo">
            <p class="vl-visor-pwa__estado" hidden></p>
            <iframe class="vl-visor-pwa__frame" title="Documento"></iframe>
        </div>
    `;

    root.querySelector('.vl-visor-pwa__cerrar').addEventListener('click', () => {
        vlCerrarVisorPwa(false);
    });
    root.querySelector('.vl-visor-pwa__descargar').addEventListener('click', () => {
        if (!visorBlobUrl) {
            return;
        }
        const a = document.createElement('a');
        a.href = visorBlobUrl;
        a.download = visorNombreDescarga;
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        a.remove();
    });

    document.body.appendChild(root);
    visorEl = root;

    return visorEl;
}

function vlSetEstado(texto) {
    const el = visorEl?.querySelector('.vl-visor-pwa__estado');
    if (!el) {
        return;
    }
    if (!texto) {
        el.hidden = true;
        el.textContent = '';
        return;
    }
    el.hidden = false;
    el.textContent = texto;
}

function vlMostrarVisor(titulo) {
    const root = vlAsegurarVisor();
    root.querySelector('.vl-visor-pwa__titulo').textContent = titulo;
    root.querySelector('.vl-visor-pwa__frame').title = titulo;
    root.removeAttribute('hidden');
    root.setAttribute('aria-hidden', 'false');
    root.classList.add('is-open');
    document.body.classList.add('vl-visor-pwa-abierto');
    visorAbierto = true;
    root.querySelector('.vl-visor-pwa__cerrar')?.focus();
}

function vlOcultarVisor() {
    if (!visorEl) {
        return;
    }
    if (visorAbort) {
        visorAbort.abort();
        visorAbort = null;
    }
    const iframe = visorEl.querySelector('.vl-visor-pwa__frame');
    iframe.src = 'about:blank';
    vlLimpiarBlob();
    visorEl.setAttribute('hidden', '');
    visorEl.setAttribute('aria-hidden', 'true');
    visorEl.classList.remove('is-open');
    document.body.classList.remove('vl-visor-pwa-abierto');
    visorAbierto = false;
    vlSetEstado('');
}

export function vlCerrarVisorPwa(desdeAtras) {
    if (!visorAbierto) {
        return;
    }
    if (!desdeAtras && visorHistoryPushed) {
        visorHistoryPushed = false;
        history.back();

        return;
    }
    visorHistoryPushed = false;
    vlOcultarVisor();
}

async function vlAbrirVisorPwa(url) {
    const root = vlAsegurarVisor();
    const iframe = root.querySelector('.vl-visor-pwa__frame');
    const titulo = vlTituloDesdeUrl(url);

    if (!visorAbierto) {
        history.pushState({ vlVisorPwa: 1 }, '', location.href);
        visorHistoryPushed = true;
    }

    if (visorAbort) {
        visorAbort.abort();
    }
    visorAbort = new AbortController();
    const signal = visorAbort.signal;

    vlLimpiarBlob();
    iframe.src = 'about:blank';
    vlMostrarVisor(titulo);
    vlSetEstado('Cargando…');
    root.querySelector('.vl-visor-pwa__descargar').disabled = true;

    try {
        const res = await fetch(url, {
            credentials: 'same-origin',
            redirect: 'follow',
            signal,
        });
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
        visorNombreDescarga = vlNombreDesdeDisposition(res.headers.get('Content-Disposition'));
        const blob = await res.blob();
        if (signal.aborted) {
            return;
        }
        const ctype = (res.headers.get('Content-Type') || blob.type || '').split(';')[0].trim().toLowerCase();
        const esPdf = ctype === 'application/pdf' || visorNombreDescarga.toLowerCase().endsWith('.pdf');
        const paraMostrar = esPdf && blob.type !== 'application/pdf'
            ? new Blob([blob], { type: 'application/pdf' })
            : blob;
        visorBlobUrl = URL.createObjectURL(paraMostrar);
        iframe.src = visorBlobUrl;
        vlSetEstado('');
        root.querySelector('.vl-visor-pwa__descargar').disabled = false;
    } catch (e) {
        if (e && e.name === 'AbortError') {
            return;
        }
        vlSetEstado('No se pudo mostrar el documento. Use Descargar o cierre e intente de nuevo.');
        iframe.src = url;
        root.querySelector('.vl-visor-pwa__descargar').disabled = true;
    }
}

export function vlAbrirUrl(url) {
    if (!url || typeof url !== 'string') {
        return;
    }
    if (vlDebeVisorInApp(url)) {
        vlAbrirVisorPwa(url);

        return;
    }
    window.open(url, '_blank', 'noopener,noreferrer');
}

export function instalarVisorPwa() {
    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0) {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        const a = event.target.closest?.('a[href][target="_blank"]');
        if (!a || a.hasAttribute('download')) {
            return;
        }
        const href = a.href;
        if (!href || href.startsWith('javascript:')) {
            return;
        }
        if (!vlDebeVisorInApp(href)) {
            return;
        }
        event.preventDefault();
        vlAbrirUrl(href);
    }, true);

    window.addEventListener('popstate', () => {
        if (visorAbierto) {
            vlCerrarVisorPwa(true);
        }
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && visorAbierto) {
            event.preventDefault();
            vlCerrarVisorPwa(false);
        }
    });

    document.addEventListener('livewire:navigate', () => {
        visorHistoryPushed = false;
        vlCerrarVisorPwa(true);
    });
}
