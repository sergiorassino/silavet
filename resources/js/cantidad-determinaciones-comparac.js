import Chart from 'chart.js/auto';

const COLOR_P1 = 'rgba(147, 167, 226, 0.85)';
const COLOR_P2 = 'rgba(120, 96, 180, 0.85)';
const COLOR_P1_BORDER = 'rgba(110, 130, 200, 1)';
const COLOR_P2_BORDER = 'rgba(90, 70, 150, 1)';

function vlReadPayload(configPayload, payloadEl) {
    if (configPayload && typeof configPayload === 'object') {
        return configPayload;
    }

    if (!payloadEl) {
        return null;
    }

    const fromAttr = payloadEl.getAttribute('data-chart-payload');
    if (fromAttr) {
        try {
            return JSON.parse(fromAttr);
        } catch {
            // continuar
        }
    }

    const raw = (payloadEl.textContent || '').trim();
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

function vlBuildDatasets(payload, chartKind) {
    const label1 = payload.label1 || 'Período 1';
    const label2 = payload.label2 || 'Período 2';
    const data1 = payload.periodo1 || [];
    const data2 = payload.periodo2 || [];

    if (chartKind === 'pie') {
        const total1 = data1.reduce((a, b) => a + Number(b || 0), 0);
        const total2 = data2.reduce((a, b) => a + Number(b || 0), 0);
        return {
            labels: [label1, label2],
            datasets: [{
                data: [total1, total2],
                backgroundColor: [COLOR_P1, COLOR_P2],
                borderColor: [COLOR_P1_BORDER, COLOR_P2_BORDER],
                borderWidth: 1,
            }],
            isPie: true,
        };
    }

    const common = {
        labels: payload.labels || [],
        datasets: [
            {
                label: label1,
                data: data1,
                backgroundColor: COLOR_P1,
                borderColor: COLOR_P1_BORDER,
                borderWidth: 1,
                fill: chartKind === 'area',
                tension: 0.25,
            },
            {
                label: label2,
                data: data2,
                backgroundColor: COLOR_P2,
                borderColor: COLOR_P2_BORDER,
                borderWidth: 1,
                fill: chartKind === 'area',
                tension: 0.25,
            },
        ],
        isPie: false,
    };

    if (chartKind === 'stacked') {
        common.datasets.forEach((ds) => {
            ds.stack = 'total';
        });
    }

    return common;
}

function vlChartType(kind) {
    if (kind === 'horizontalBar') {
        return 'bar';
    }
    if (kind === 'area' || kind === 'stacked') {
        return kind === 'area' ? 'line' : 'bar';
    }
    if (kind === 'pie') {
        return 'pie';
    }
    return kind === 'line' ? 'line' : 'bar';
}

function vlCantidadDeterminacionesChartFactory(config = {}) {
    return {
        chart: null,
        exportOpen: false,
        chartPdfUrl: config.chartPdfUrl || '',
        csrf: config.csrf || '',
        query: config.query || {},
        payload: config.payload || null,

        init() {
            this.$nextTick(() => this.renderChart());
        },

        renderChart() {
            const canvas = this.$refs.canvas;
            if (!canvas) {
                return;
            }

            const payload = vlReadPayload(this.payload, this.$refs.payload);
            if (!payload) {
                console.warn('[vl-cdc] Sin payload de gráfico');
                return;
            }

            this.payload = payload;

            const kind = payload.tipo || 'bar';
            const built = vlBuildDatasets(payload, kind);
            const type = vlChartType(kind);

            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }

            this.chart = new Chart(canvas.getContext('2d'), {
                type,
                data: {
                    labels: built.labels,
                    datasets: built.datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: kind === 'horizontalBar' ? 'y' : 'x',
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { mode: built.isPie ? 'nearest' : 'index', intersect: false },
                    },
                    scales: built.isPie
                        ? {}
                        : {
                            x: {
                                stacked: kind === 'stacked',
                                title: { display: true, text: 'Determinación' },
                                ticks: { maxRotation: 45, minRotation: 0, autoSkip: true },
                            },
                            y: {
                                stacked: kind === 'stacked',
                                beginAtZero: true,
                                ticks: { precision: 0 },
                            },
                        },
                },
            });
        },

        async exportarGraficoPdf() {
            if (!this.chart || !this.chartPdfUrl) {
                window.vlSwalError?.('No hay gráfico para exportar.');
                return;
            }

            const chartImage = this.chart.toBase64Image('image/png', 1);
            const body = new FormData();
            body.append('_token', this.csrf);
            body.append('chartImage', chartImage);

            const q = this.query || {};
            Object.keys(q).forEach((key) => {
                const val = q[key];
                if (Array.isArray(val)) {
                    val.forEach((item) => body.append(`${key}[]`, item));
                } else if (val !== null && val !== undefined && val !== '') {
                    body.append(key, val);
                }
            });

            try {
                const resp = await fetch(this.chartPdfUrl, {
                    method: 'POST',
                    body,
                    headers: {
                        Accept: 'application/pdf',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!resp.ok) {
                    throw new Error(`HTTP ${resp.status}`);
                }

                const blob = await resp.blob();
                const url = URL.createObjectURL(blob);
                window.open(url, '_blank', 'noopener,noreferrer');
                setTimeout(() => URL.revokeObjectURL(url), 60_000);
            } catch (e) {
                console.error(e);
                window.vlSwalError?.('No se pudo exportar el gráfico a PDF.');
            }
        },
    };
}

window.vlCantidadDeterminacionesChart = vlCantidadDeterminacionesChartFactory;

document.addEventListener('alpine:init', () => {
    if (typeof Alpine === 'undefined') {
        return;
    }
    Alpine.data('vlCantidadDeterminacionesChart', vlCantidadDeterminacionesChartFactory);
});
