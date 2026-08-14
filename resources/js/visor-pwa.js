/**
 * Visor in-app para PWA instalada en celular.
 *
 * En standalone, abrir un PDF del mismo origen (target=_blank / iframe)
 * crea otra ventana de la app. Chrome no dibuja PDFs en iframe y esa
 * ventana con cruz cierra el sistema. Se renderiza con PDF.js en overlay.
 */

let visorEl = null;
let visorBlobUrl = null;
let visorAbort = null;
let visorHistoryPushed = false;
let visorNombreDescarga = 'documento.pdf';
let visorAbierto = false;
let visorPdfDoc = null;
let visorPdfJs = null;
let visorPintando = false;
let visorRepintarPendiente = false;
let visorResizeTimer = null;
let visorScrollY = 0;

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
            // filename= simple
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

async function vlDestruirPdf() {
    if (visorPdfDoc) {
        try {
            await visorPdfDoc.destroy();
        } catch {
            // ignore
        }
        visorPdfDoc = null;
    }
}

function vlPaginasEl() {
    return visorEl?.querySelector('.vl-visor-pwa__paginas') ?? null;
}

function vlVaciarPaginas() {
    const paginas = vlPaginasEl();
    if (paginas) {
        paginas.replaceChildren();
    }
}

async function cargarPdfJs() {
    if (visorPdfJs) {
        return visorPdfJs;
    }

    const mod = await import('./visor-pdfjs.js');
    visorPdfJs = mod.obtenerPdfJs();

    return visorPdfJs;
}

function vlEsPdfBinario(bytes) {
    return bytes.length >= 5
        && bytes[0] === 0x25
        && bytes[1] === 0x50
        && bytes[2] === 0x44
        && bytes[3] === 0x46;
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
            <div class="vl-visor-pwa__paginas"></div>
        </div>
    `;

    root.querySelector('.vl-visor-pwa__cerrar').addEventListener('click', () => {
        vlCerrarVisorPwa(false);
    });
    root.querySelector('.vl-visor-pwa__descargar').addEventListener('click', async () => {
        if (!visorBlobUrl) {
            return;
        }
        try {
            const blob = await fetch(visorBlobUrl).then((r) => r.blob());
            const file = new File([blob], visorNombreDescarga, { type: 'application/pdf' });
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({ files: [file], title: visorNombreDescarga });

                return;
            }
        } catch {
            // Canceló compartir o no hay soporte: descargar.
        }
        const a = document.createElement('a');
        a.href = visorBlobUrl;
        a.download = visorNombreDescarga;
        a.rel = 'noopener';
        a.target = '_self';
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

function vlAjustarVisorViewport() {
    if (!visorEl) {
        return;
    }
    const vv = window.visualViewport;
    visorEl.style.position = 'fixed';
    visorEl.style.right = 'auto';
    visorEl.style.bottom = 'auto';
    if (!vv) {
        visorEl.style.top = '0px';
        visorEl.style.left = '0px';
        visorEl.style.width = '100%';
        visorEl.style.height = '100%';

        return;
    }
    visorEl.style.top = `${Math.round(vv.offsetTop)}px`;
    visorEl.style.left = `${Math.round(vv.offsetLeft)}px`;
    visorEl.style.width = `${Math.round(vv.width)}px`;
    visorEl.style.height = `${Math.round(vv.height)}px`;
}

function vlBloquearFondo() {
    visorScrollY = window.scrollY || window.pageYOffset || 0;
    document.documentElement.classList.add('vl-visor-pwa-abierto');
    document.body.classList.add('vl-visor-pwa-abierto');
    document.body.style.top = `-${visorScrollY}px`;
    const shell = document.getElementById('vl-shell');
    if (shell) {
        shell.setAttribute('inert', '');
        shell.setAttribute('aria-hidden', 'true');
    }
    vlAjustarVisorViewport();
}

function vlDesbloquearFondo() {
    document.documentElement.classList.remove('vl-visor-pwa-abierto');
    document.body.classList.remove('vl-visor-pwa-abierto');
    document.body.style.top = '';
    const shell = document.getElementById('vl-shell');
    if (shell) {
        shell.removeAttribute('inert');
        shell.removeAttribute('aria-hidden');
    }
    window.scrollTo(0, visorScrollY);
}

function vlMostrarVisor(titulo) {
    const root = vlAsegurarVisor();
    root.querySelector('.vl-visor-pwa__titulo').textContent = titulo;
    root.removeAttribute('hidden');
    root.setAttribute('aria-hidden', 'false');
    root.classList.add('is-open');
    visorAbierto = true;
    vlBloquearFondo();
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
    clearTimeout(visorResizeTimer);
    vlVaciarPaginas();
    vlDestruirPdf();
    vlLimpiarBlob();
    visorEl.setAttribute('hidden', '');
    visorEl.setAttribute('aria-hidden', 'true');
    visorEl.classList.remove('is-open');
    visorAbierto = false;
    vlSetEstado('');
    vlDesbloquearFondo();
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

function vlAnchoPaginas() {
    const cuerpo = visorEl?.querySelector('.vl-visor-pwa__cuerpo');
    const w = cuerpo?.clientWidth || window.visualViewport?.width || window.innerWidth || 320;

    return Math.max(280, Math.floor(w));
}

async function vlPintarPaginas(signal) {
    if (!visorPdfDoc || !visorAbierto) {
        return;
    }
    if (visorPintando) {
        visorRepintarPendiente = true;

        return;
    }

    visorPintando = true;
    const paginas = vlPaginasEl();

    try {
        vlVaciarPaginas();
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const cssWidth = vlAnchoPaginas();

        for (let n = 1; n <= visorPdfDoc.numPages; n += 1) {
            if (signal?.aborted || !visorAbierto) {
                return;
            }
            const page = await visorPdfDoc.getPage(n);
            const unscaled = page.getViewport({ scale: 1 });
            const scale = (cssWidth / unscaled.width) * dpr;
            const viewport = page.getViewport({ scale });
            const canvas = document.createElement('canvas');
            canvas.className = 'vl-visor-pwa__canvas';
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);
            canvas.style.width = '100%';
            canvas.style.height = 'auto';
            paginas.appendChild(canvas);
            await page.render({
                canvasContext: canvas.getContext('2d', { alpha: false }),
                viewport,
            }).promise;
        }
    } finally {
        visorPintando = false;
        if (visorRepintarPendiente && visorAbierto && !signal?.aborted) {
            visorRepintarPendiente = false;
            await vlPintarPaginas(signal);
        }
    }
}

function vlProgramarRepintado() {
    if (!visorAbierto) {
        return;
    }
    vlAjustarVisorViewport();
    if (!visorPdfDoc) {
        return;
    }
    clearTimeout(visorResizeTimer);
    visorResizeTimer = window.setTimeout(() => {
        vlPintarPaginas(visorAbort?.signal);
    }, 180);
}

async function vlRenderPdf(bytes, signal) {
    const pdfjs = await cargarPdfJs();
    if (signal.aborted) {
        return;
    }

    const loadingTask = pdfjs.getDocument({ data: bytes });
    const pdf = await loadingTask.promise;
    if (signal.aborted) {
        await pdf.destroy();

        return;
    }
    visorPdfDoc = pdf;
    await vlPintarPaginas(signal);
}

async function vlAbrirVisorPwa(url) {
    const root = vlAsegurarVisor();
    const titulo = vlTituloDesdeUrl(url);

    if (!visorAbierto) {
        history.pushState({ vlVisorPwa: 1 }, '', location.href);
        visorHistoryPushed = true;
    }

    if (visorAbort) {
        visorAbort.abort();
    }
    visorAbort = new AbortController();
    const { signal } = visorAbort;

    await vlDestruirPdf();
    vlLimpiarBlob();
    vlVaciarPaginas();
    vlMostrarVisor(titulo);
    await new Promise((resolve) => {
        requestAnimationFrame(() => requestAnimationFrame(resolve));
    });
    vlSetEstado('Cargando…');
    root.querySelector('.vl-visor-pwa__descargar').disabled = true;

    try {
        const res = await fetch(url, {
            credentials: 'include',
            redirect: 'follow',
            signal,
        });
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        visorNombreDescarga = vlNombreDesdeDisposition(res.headers.get('Content-Disposition'));
        const buffer = await res.arrayBuffer();
        if (signal.aborted) {
            return;
        }

        const ctype = (res.headers.get('Content-Type') || '').split(';')[0].trim().toLowerCase();
        const bytes = new Uint8Array(buffer.slice(0));
        const blob = new Blob([buffer], { type: ctype || 'application/pdf' });
        visorBlobUrl = URL.createObjectURL(blob);
        root.querySelector('.vl-visor-pwa__descargar').disabled = false;

        if (ctype.startsWith('image/')) {
            const img = document.createElement('img');
            img.className = 'vl-visor-pwa__img';
            img.alt = titulo;
            img.src = visorBlobUrl;
            vlPaginasEl().appendChild(img);
            vlSetEstado('');

            return;
        }

        if (!vlEsPdfBinario(bytes)) {
            vlSetEstado('No se recibió el PDF (puede haber caducado la sesión). Cierre, vuelva a ingresar e intente de nuevo.');

            return;
        }

        await vlRenderPdf(bytes, signal);
        if (!signal.aborted) {
            vlSetEstado('');
        }
    } catch (e) {
        if (e && e.name === 'AbortError') {
            return;
        }
        console.error('[vl-visor-pwa]', e);
        vlSetEstado('No se pudo mostrar el informe. Use el botón de descarga o cierre e intente de nuevo.');
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
        if (event.button !== 0) {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        const a = event.target.closest?.('a[href]');
        if (!a || a.hasAttribute('download')) {
            return;
        }
        const target = (a.getAttribute('target') || '').toLowerCase();
        if (target !== '_blank') {
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
        event.stopImmediatePropagation();
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

    window.addEventListener('resize', vlProgramarRepintado);
    window.addEventListener('orientationchange', vlProgramarRepintado);
    window.visualViewport?.addEventListener('resize', vlProgramarRepintado);
    window.visualViewport?.addEventListener('scroll', vlAjustarVisorViewport);

    document.addEventListener('touchmove', (event) => {
        if (!visorAbierto) {
            return;
        }
        const cuerpo = visorEl?.querySelector('.vl-visor-pwa__cuerpo');
        if (cuerpo && cuerpo.contains(event.target)) {
            return;
        }
        event.preventDefault();
    }, { passive: false });
}
