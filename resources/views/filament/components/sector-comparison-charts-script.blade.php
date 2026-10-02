<script>
    (() => {
        if (window.reputalisSectorComparisonChartsReady) {
            window.reputalisInitSectorComparisonCharts?.();
            return;
        }

        window.reputalisSectorComparisonChartsReady = true;

        const loadApexCharts = () => {
            if (window.ApexCharts) {
                return Promise.resolve(window.ApexCharts);
            }

            if (window.reputalisApexChartsPromise) {
                return window.reputalisApexChartsPromise;
            }

            window.reputalisApexChartsPromise = new Promise((resolve, reject) => {
                const existingScript = document.querySelector('script[data-reputalis-apexcharts]');
                if (existingScript) {
                    existingScript.addEventListener('load', () => resolve(window.ApexCharts));
                    existingScript.addEventListener('error', reject);
                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                script.async = true;
                script.dataset.reputalisApexcharts = '1';
                script.onload = () => resolve(window.ApexCharts);
                script.onerror = (error) => {
                    window.reputalisApexChartsPromise = null;
                    reject(error);
                };
                document.head.appendChild(script);
            });

            return window.reputalisApexChartsPromise;
        };

        const destroyChart = (element) => {
            if (element?._reputalisChart) {
                try {
                    element._reputalisChart.destroy();
                } catch (_error) {
                    // ignore
                }
                element._reputalisChart = null;
            }
        };

        const parseConfig = (card) => {
            const node = card.querySelector('[data-sector-scope-config]');
            if (!node) {
                return null;
            }
            try {
                return JSON.parse(node.textContent || '{}');
            } catch (_error) {
                return null;
            }
        };

        const ink = '#12353c';
        const muted = '#7b9197';
        const grid = 'rgba(18, 53, 60, 0.08)';
        const youColor = '#2ad4dc';
        const avgColor = '#f59e0b';

        const renderScatter = async (element, config) => {
            const ApexCharts = await loadApexCharts();
            destroyChart(element);

            const points = config.points || [];
            const others = points.filter((p) => !p.is_you).map((p) => ({
                x: Number(p.x),
                y: Number(p.y),
                name: p.name,
                position: p.position,
            }));
            const you = points.filter((p) => p.is_you).map((p) => ({
                x: Number(p.x),
                y: Number(p.y),
                name: p.name,
                position: p.position,
            }));

            element._reputalisChart = new ApexCharts(element, {
                chart: {
                    type: 'scatter',
                    height: 320,
                    toolbar: { show: false },
                    animations: { enabled: false },
                    background: 'transparent',
                    zoom: { enabled: false },
                },
                series: [
                    { name: config.youLabel || 'Tú', data: you },
                    { name: 'Otros', data: others },
                ],
                colors: [youColor, '#94a3b8'],
                markers: {
                    size: [11, 5],
                    strokeWidth: [2, 0],
                    strokeColors: ['#fff', 'transparent'],
                },
                legend: { show: false },
                grid: {
                    borderColor: grid,
                    strokeDashArray: 0,
                    padding: { left: 8, right: 16, top: 8, bottom: 0 },
                },
                xaxis: {
                    type: 'numeric',
                    min: 3.6,
                    max: 5,
                    tickAmount: 6,
                    title: {
                        text: config.xLabel || 'Nota',
                        style: { color: muted, fontSize: '11px', fontWeight: 600 },
                    },
                    labels: {
                        style: { colors: muted, fontSize: '11px' },
                        formatter: (v) => Number(v).toFixed(1),
                    },
                    axisBorder: { color: grid },
                    axisTicks: { show: false },
                },
                yaxis: {
                    min: 0,
                    title: {
                        text: config.yLabel || 'Reseñas',
                        style: { color: muted, fontSize: '11px', fontWeight: 600 },
                    },
                    labels: {
                        style: { colors: muted, fontSize: '11px' },
                    },
                },
                annotations: {
                    xaxis: [{
                        x: Number(config.average || 0),
                        borderColor: avgColor,
                        strokeDashArray: 6,
                        label: {
                            text: `${config.averageLabel || 'MEDIA'} ${Number(config.average || 0).toFixed(2)}`,
                            style: {
                                color: '#fff',
                                background: avgColor,
                                fontSize: '10px',
                                fontWeight: 700,
                            },
                        },
                    }],
                },
                tooltip: {
                    custom: ({ seriesIndex, dataPointIndex, w }) => {
                        const point = w.config.series[seriesIndex].data[dataPointIndex];
                        if (!point) {
                            return '';
                        }
                        // Escapar <\/div> para que libxml/Livewire no lo traten como HTML real dentro del <script>.
                        return `<div style="padding:.55rem .7rem;font-size:12px;color:${ink}">
                            <strong>${point.name || ''}</strong><br/>
                            ${Number(point.x).toFixed(1)} · ${point.y} reseñas<br/>
                            #${point.position || '—'}
                        <\/div>`;
                    },
                },
            });

            await element._reputalisChart.render();
        };

        const renderHistogram = async (element, config) => {
            const ApexCharts = await loadApexCharts();
            destroyChart(element);

            const categories = config.categories || [];
            const counts = config.counts || [];

            element._reputalisChart = new ApexCharts(element, {
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: { show: false },
                    animations: { enabled: false },
                    background: 'transparent',
                },
                series: [{ name: '', data: counts }],
                colors: ['#9db5bb'],
                plotOptions: {
                    bar: {
                        columnWidth: '70%',
                        borderRadius: 3,
                    },
                },
                dataLabels: { enabled: false },
                legend: { show: false },
                grid: {
                    borderColor: grid,
                    strokeDashArray: 0,
                    padding: { left: 8, right: 16, top: 8, bottom: 0 },
                },
                xaxis: {
                    categories,
                    labels: {
                        rotate: -35,
                        hideOverlappingLabels: true,
                        style: { colors: muted, fontSize: '10px' },
                    },
                    axisBorder: { color: grid },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: muted, fontSize: '11px' },
                    },
                },
                annotations: {
                    xaxis: [
                        {
                            x: String(Number(config.average || 0).toFixed(1)),
                            borderColor: avgColor,
                            strokeDashArray: 6,
                            label: {
                                text: `${config.averageLabel || 'MEDIA'} ${Number(config.average || 0).toFixed(2)}`,
                                style: {
                                    color: '#fff',
                                    background: avgColor,
                                    fontSize: '10px',
                                    fontWeight: 700,
                                },
                            },
                        },
                        {
                            x: String(Number(config.your_rating || 0).toFixed(1)),
                            borderColor: youColor,
                            strokeDashArray: 0,
                            label: {
                                text: `${config.youLabel || 'TÚ'} ${Number(config.your_rating || 0).toFixed(1)}`,
                                style: {
                                    color: ink,
                                    background: youColor,
                                    fontSize: '10px',
                                    fontWeight: 700,
                                },
                            },
                        },
                    ],
                },
                tooltip: {
                    y: {
                        formatter: (v) => `${v}`,
                    },
                },
            });

            await element._reputalisChart.render();
        };

        const renderEvolution = async (element, config) => {
            const ApexCharts = await loadApexCharts();
            destroyChart(element);

            const labels = config.labels || [];
            const positions = (config.positions || []).map((v) => Number(v));
            const analyzed = Number(config.analyzed || 0);

            element._reputalisChart = new ApexCharts(element, {
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: { show: false },
                    animations: { enabled: false },
                    background: 'transparent',
                    zoom: { enabled: false },
                },
                series: [{
                    name: config.seriesLabel || 'Posición',
                    data: positions,
                }],
                colors: [youColor],
                stroke: {
                    curve: 'smooth',
                    width: 3,
                },
                markers: {
                    size: 6,
                    strokeWidth: 2,
                    strokeColors: '#fff',
                    colors: [youColor],
                },
                dataLabels: {
                    enabled: true,
                    offsetY: -8,
                    formatter: (v) => {
                        const pos = Math.round(Number(v));
                        return analyzed > 0 ? `${pos}.º / ${analyzed}` : `${pos}.º`;
                    },
                    style: {
                        fontSize: '11px',
                        fontWeight: 700,
                        colors: [ink],
                    },
                    background: {
                        enabled: true,
                        borderRadius: 6,
                        borderWidth: 0,
                        foreColor: ink,
                        padding: 4,
                        opacity: 1,
                        dropShadow: { enabled: false },
                    },
                },
                legend: { show: false },
                grid: {
                    borderColor: grid,
                    strokeDashArray: 0,
                    padding: { left: 8, right: 16, top: 18, bottom: 0 },
                },
                xaxis: {
                    categories: labels,
                    labels: {
                        style: { colors: muted, fontSize: '11px' },
                    },
                    axisBorder: { color: grid },
                    axisTicks: { show: false },
                },
                yaxis: {
                    reversed: true,
                    min: 1,
                    forceNiceScale: true,
                    labels: {
                        style: { colors: muted, fontSize: '11px' },
                        formatter: (v) => `${Math.round(Number(v))}º`,
                    },
                },
                tooltip: {
                    y: {
                        formatter: (v) => {
                            const pos = Math.round(Number(v));
                            return analyzed > 0 ? `${pos}.º / ${analyzed}` : `${pos}.º`;
                        },
                    },
                },
            });

            await element._reputalisChart.render();
        };

        const renderCard = async (card) => {
            const config = parseConfig(card);
            if (!config) {
                return;
            }

            const signature = JSON.stringify(config);
            if (card._sectorChartsSignature === signature) {
                return;
            }

            const scatterEl = card.querySelector('[data-sector-chart="scatter"]');
            const histogramEl = card.querySelector('[data-sector-chart="histogram"]');
            const evolutionEl = card.querySelector('[data-sector-chart="evolution"]');

            if (scatterEl && config.scatter) {
                await renderScatter(scatterEl, config.scatter);
            }
            if (histogramEl && config.histogram) {
                await renderHistogram(histogramEl, config.histogram);
            }
            if (evolutionEl && config.evolution) {
                await renderEvolution(evolutionEl, config.evolution);
            }

            card._sectorChartsSignature = signature;
        };

        window.reputalisInitSectorComparisonCharts = () => {
            document.querySelectorAll('[data-sector-scope]').forEach((card) => {
                renderCard(card).catch(() => {
                    // ignore
                });
            });
        };

        document.addEventListener('DOMContentLoaded', window.reputalisInitSectorComparisonCharts);
        document.addEventListener('livewire:navigated', window.reputalisInitSectorComparisonCharts);
        window.addEventListener('load', () => window.reputalisInitSectorComparisonCharts?.());

        if (window.Livewire?.hook) {
            window.Livewire.hook('morphed', () => {
                window.setTimeout(() => window.reputalisInitSectorComparisonCharts?.(), 60);
            });
        }

        window.reputalisInitSectorComparisonCharts();
        [80, 200, 500].forEach((delay) => {
            window.setTimeout(() => window.reputalisInitSectorComparisonCharts?.(), delay);
        });
    })();
</script>
