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
let visorZoom = 1;
let visorPanX = 0;
let visorPanY = 0;
let visorPinch = null;
let visorArrastre = null;
let visorUltimoTap = 0;
let visorAnchoPintado = 0;

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
    vlInstalarZoomPdf(root.querySelector('.vl-visor-pwa__cuerpo'));

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

function vlCuerpoEl() {
    return visorEl?.querySelector('.vl-visor-pwa__cuerpo') ?? null;
}

function vlResetZoomPdf() {
    visorZoom = 1;
    visorPanX = 0;
    visorPanY = 0;
    visorPinch = null;
    visorArrastre = null;
    vlAplicarZoomPdf();
}

function vlAplicarZoomPdf() {
    const paginas = vlPaginasEl();
    const cuerpo = vlCuerpoEl();
    if (!paginas) {
        return;
    }
    paginas.style.transformOrigin = '0 0';
    paginas.style.transform = visorZoom === 1 && visorPanX === 0 && visorPanY === 0
        ? ''
        : `translate(${visorPanX}px, ${visorPanY}px) scale(${visorZoom})`;
    if (cuerpo) {
        cuerpo.style.touchAction = visorZoom > 1.02 ? 'none' : 'pan-y';
    }
}

function vlLimitarPanPdf() {
    const cuerpo = vlCuerpoEl();
    const paginas = vlPaginasEl();
    if (!cuerpo || !paginas) {
        return;
    }
    const cw = cuerpo.clientWidth;
    const ch = cuerpo.clientHeight;
    const pw = paginas.offsetWidth * visorZoom;
    const ph = paginas.offsetHeight * visorZoom;
    const minX = Math.min(0, cw - pw);
    const minY = Math.min(0, ch - ph);
    visorPanX = Math.min(0, Math.max(minX, visorPanX));
    visorPanY = Math.min(0, Math.max(minY, visorPanY));
}

function vlScrollNativoAPan() {
    const cuerpo = vlCuerpoEl();
    if (!cuerpo) {
        return;
    }
    visorPanX -= cuerpo.scrollLeft;
    visorPanY -= cuerpo.scrollTop;
    cuerpo.scrollLeft = 0;
    cuerpo.scrollTop = 0;
}

function vlPanAScrollNativo() {
    const cuerpo = vlCuerpoEl();
    if (!cuerpo || visorZoom > 1.02) {
        return;
    }
    const y = Math.max(0, -visorPanY);
    visorPanX = 0;
    visorPanY = 0;
    visorZoom = 1;
    vlAplicarZoomPdf();
    cuerpo.scrollTop = y;
}

function vlPuntoEnCuerpo(t1, t2) {
    const cuerpo = vlCuerpoEl();
    const rect = cuerpo.getBoundingClientRect();
    const x = ((t2 ? (t1.clientX + t2.clientX) / 2 : t1.clientX) - rect.left);
    const y = ((t2 ? (t1.clientY + t2.clientY) / 2 : t1.clientY) - rect.top);

    return { x, y };
}

function vlZoomHacia(nuevo, punto) {
    const scale = Math.min(4, Math.max(1, nuevo));
    const cx = (punto.x - visorPanX) / visorZoom;
    const cy = (punto.y - visorPanY) / visorZoom;
    visorZoom = scale;
    visorPanX = punto.x - cx * visorZoom;
    visorPanY = punto.y - cy * visorZoom;
    vlLimitarPanPdf();
    vlAplicarZoomPdf();
}

function vlInstalarZoomPdf(cuerpo) {
    cuerpo.addEventListener('touchstart', (event) => {
        if (!visorAbierto) {
            return;
        }
        if (event.touches.length === 2) {
            event.preventDefault();
            vlScrollNativoAPan();
            const [a, b] = event.touches;
            const punto = vlPuntoEnCuerpo(a, b);
            visorPinch = {
                dist: Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY),
                zoom: visorZoom,
                cx: (punto.x - visorPanX) / visorZoom,
                cy: (punto.y - visorPanY) / visorZoom,
            };
            visorArrastre = null;

            return;
        }
        if (event.touches.length === 1 && visorZoom > 1.02) {
            visorArrastre = {
                x: event.touches[0].clientX,
                y: event.touches[0].clientY,
                panX: visorPanX,
                panY: visorPanY,
            };
        }
    }, { passive: false });

    cuerpo.addEventListener('touchmove', (event) => {
        if (!visorAbierto) {
            return;
        }
        if (event.touches.length === 2 && visorPinch) {
            event.preventDefault();
            const [a, b] = event.touches;
            const dist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
            if (visorPinch.dist < 8) {
                return;
            }
            const factor = dist / visorPinch.dist;
            const punto = vlPuntoEnCuerpo(a, b);
            visorZoom = Math.min(4, Math.max(1, visorPinch.zoom * factor));
            visorPanX = punto.x - visorPinch.cx * visorZoom;
            visorPanY = punto.y - visorPinch.cy * visorZoom;
            vlLimitarPanPdf();
            vlAplicarZoomPdf();

            return;
        }
        if (event.touches.length === 1 && visorArrastre && visorZoom > 1.02) {
            event.preventDefault();
            const t = event.touches[0];
            visorPanX = visorArrastre.panX + (t.clientX - visorArrastre.x);
            visorPanY = visorArrastre.panY + (t.clientY - visorArrastre.y);
            vlLimitarPanPdf();
            vlAplicarZoomPdf();
        }
    }, { passive: false });

    cuerpo.addEventListener('touchend', (event) => {
        if (!visorAbierto) {
            return;
        }
        if (event.touches.length < 2) {
            if (visorPinch) {
                visorUltimoTap = 0;
            }
            visorPinch = null;
        }
        if (event.touches.length === 0) {
            visorArrastre = null;
            if (visorZoom <= 1.02) {
                vlPanAScrollNativo();
            }
            const t = event.changedTouches[0];
            if (t && event.touches.length === 0) {
                const ahora = Date.now();
                if (ahora - visorUltimoTap < 280) {
                    event.preventDefault();
                    const punto = vlPuntoEnCuerpo(t, null);
                    if (visorZoom > 1.05) {
                        visorZoom = 1;
                        visorPanX = 0;
                        visorPanY = 0;
                        vlAplicarZoomPdf();
                        vlPanAScrollNativo();
                    } else {
                        vlScrollNativoAPan();
                        vlZoomHacia(2.4, punto);
                    }
                    visorUltimoTap = 0;
                } else {
                    visorUltimoTap = ahora;
                }
            }
        }
    }, { passive: false });
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
    vlResetZoomPdf();
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
        vlResetZoomPdf();
        vlVaciarPaginas();
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const cssWidth = vlAnchoPaginas();
        visorAnchoPintado = cssWidth;

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
    if (!visorAbierto || !visorPdfDoc) {
        return;
    }
    const w = window.innerWidth;
    if (visorAnchoPintado > 0 && Math.abs(w - visorAnchoPintado) < 80) {
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

    window.addEventListener('orientationchange', vlProgramarRepintado);

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
