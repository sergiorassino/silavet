import * as pdfjsLib from 'pdfjs-dist';
import PdfJsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?worker&inline';

let workerPort = null;

/**
 * PDF.js con worker embebido (blob). Evita /build/... absoluto y .mjs en Apache,
 * que en PWA con subcarpeta dejan el informe en blanco.
 */
export function obtenerPdfJs() {
    if (!workerPort) {
        workerPort = new PdfJsWorker();
        pdfjsLib.GlobalWorkerOptions.workerPort = workerPort;
    }

    return pdfjsLib;
}
