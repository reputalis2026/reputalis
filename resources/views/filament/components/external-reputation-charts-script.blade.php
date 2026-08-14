<script>
    (() => {
        if (window.reputalisExternalReputationChartsReady) {
            window.reputalisInitExternalReputationCharts?.();
            return;
        }

        window.reputalisExternalReputationChartsReady = true;

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

        const hasRenderedChart = (element) => Boolean(
            element?._reputalisChart && element.querySelector('.apexcharts-canvas'),
        );

        const isVisibleForRender = (element) => {
            if (!element) {
                return false;
            }
            const rect = element.getBoundingClientRect();
            return rect.width > 8 && rect.height > 8;
        };

        const theme = () => {
            const dark = document.documentElement.classList.contains('dark');
            return {
                valueColor: dark ? '#e5e7eb' : '#334155',
                gridColor: dark
                    ? 'rgba(148, 163, 184, 0.18)'
                    : 'rgba(100, 116, 139, 0.24)',
            };
        };

        const parseConfig = (card) => {
            const node = card.querySelector('[data-external-reputation-chart-config]');
            if (!node) {
                return null;
            }
            try {
                return JSON.parse(node.textContent || '{}');
            } catch (_error) {
                return null;
            }
        };

        let retryTimer = null;
        const queueRetry = (delay = 120) => {
            window.clearTimeout(retryTimer);
            retryTimer = window.setTimeout(() => {
                window.reputalisInitExternalReputationCharts?.();
            }, delay);
        };

        const renderRatingChart = async (card, config) => {
            const chartElement = card.querySelector('[data-external-reputation-chart="rating"]');
            if (!chartElement) {
                return;
            }

            const signature = JSON.stringify({ type: 'rating', config });
            if (card._renderingSignature === signature) {
                return;
            }
            if (card._chartSignature === signature && hasRenderedChart(chartElement)) {
                return;
            }
            if (!isVisibleForRender(chartElement) && !isVisibleForRender(card)) {
                queueRetry();
                return;
            }

            card._renderingSignature = signature;

            try {
                const ApexCharts = await loadApexCharts();
                await new Promise((resolve) => window.requestAnimationFrame(resolve));

                if (!isVisibleForRender(chartElement) && !isVisibleForRender(card)) {
                    queueRetry();
                    return;
                }

                destroyChart(chartElement);

                const labels = config.labels || [];
                const { valueColor, gridColor } = theme();
                const series = config.series || {};
                const xLabelRotate = labels.length > 12 ? -35 : 0;

                chartElement._reputalisChart = new ApexCharts(chartElement, {
                    chart: {
                        type: 'line',
                        height: 238,
                        parentHeightOffset: 0,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        animations: { enabled: false },
                        background: 'transparent',
                    },
                    series: [
                        {
                            name: series.google || 'Google',
                            data: (config.ratings || []).map((v) => (v === null ? null : Number(v))),
                        },
                        {
                            name: series.calculated || 'Calculada',
                            data: (config.calculated || []).map((v) => (v === null ? null : Number(v))),
                        },
                    ],
                    colors: ['#6ea1cb', '#76a99c'],
                    stroke: {
                        curve: 'straight',
                        width: 3,
                    },
                    markers: {
                        size: labels.length > 40 ? 0 : 5,
                        strokeWidth: 0,
                        colors: ['#6ea1cb', '#76a99c'],
                    },
                    dataLabels: { enabled: false },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                        labels: { colors: valueColor },
                        markers: { width: 10, height: 10, radius: 10 },
                    },
                    xaxis: {
                        categories: labels,
                        labels: {
                            rotate: xLabelRotate,
                            hideOverlappingLabels: true,
                            trim: true,
                            style: {
                                colors: valueColor,
                                fontSize: '11px',
                                fontWeight: 500,
                            },
                        },
                        axisBorder: { color: '#111827' },
                        axisTicks: { show: false },
                        tooltip: { enabled: false },
                    },
                    yaxis: {
                        min: 0,
                        max: 5,
                        tickAmount: 5,
                        decimalsInFloat: 0,
                        title: {
                            text: series.google || 'Nota',
                            style: {
                                color: valueColor,
                                fontSize: '11px',
                                fontWeight: 600,
                            },
                        },
                        labels: {
                            style: {
                                colors: valueColor,
                                fontSize: '11px',
                            },
                        },
                    },
                    grid: {
                        borderColor: gridColor,
                        strokeDashArray: 0,
                        padding: { top: 8, right: 36, bottom: 0, left: 8 },
                    },
                    tooltip: {
                        shared: true,
                        marker: { show: false },
                        y: {
                            formatter: (value) => (value === null || value === undefined
                                ? ''
                                : `${Number(value).toFixed(2)} / 5`),
                        },
                    },
                    noData: {
                        text: config.empty_label || '',
                        align: 'center',
                        verticalAlign: 'middle',
                        style: { color: valueColor, fontSize: '13px' },
                    },
                });

                await chartElement._reputalisChart.render();
                card._chartSignature = signature;
            } finally {
                card._renderingSignature = null;
            }
        };

        const renderTotalChart = async (card, config) => {
            const chartElement = card.querySelector('[data-external-reputation-chart="total"]');
            if (!chartElement) {
                return;
            }

            const signature = JSON.stringify({ type: 'total', config });
            if (card._renderingSignature === signature) {
                return;
            }
            if (card._chartSignature === signature && hasRenderedChart(chartElement)) {
                return;
            }
            if (!isVisibleForRender(chartElement) && !isVisibleForRender(card)) {
                queueRetry();
                return;
            }

            card._renderingSignature = signature;

            try {
                const ApexCharts = await loadApexCharts();
                await new Promise((resolve) => window.requestAnimationFrame(resolve));

                if (!isVisibleForRender(chartElement) && !isVisibleForRender(card)) {
                    queueRetry();
                    return;
                }

                destroyChart(chartElement);

                const labels = config.labels || [];
                const totals = (config.totals || []).map((v) => Number(v || 0));
                const maxCount = Math.max(...totals, 0);
                const { valueColor, gridColor } = theme();
                const series = config.series || {};
                const xLabelRotate = labels.length > 12 ? -35 : 0;

                chartElement._reputalisChart = new ApexCharts(chartElement, {
                    chart: {
                        type: 'area',
                        height: 232,
                        parentHeightOffset: 0,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        animations: { enabled: false },
                        background: 'transparent',
                    },
                    series: [{
                        name: series.total || 'Reseñas',
                        data: totals,
                    }],
                    colors: ['#76a99c'],
                    stroke: {
                        curve: 'smooth',
                        width: 3,
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 0,
                            opacityFrom: 0.28,
                            opacityTo: 0.04,
                            stops: [0, 90, 100],
                        },
                    },
                    markers: {
                        size: totals.length > 36 ? 0 : 4,
                        strokeWidth: 0,
                        colors: ['#76a99c'],
                    },
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    xaxis: {
                        categories: labels,
                        labels: {
                            rotate: xLabelRotate,
                            hideOverlappingLabels: true,
                            trim: true,
                            style: {
                                colors: valueColor,
                                fontSize: '11px',
                                fontWeight: 500,
                            },
                        },
                        axisBorder: { color: gridColor },
                        axisTicks: { show: false },
                        tooltip: { enabled: false },
                    },
                    yaxis: {
                        min: 0,
                        max: maxCount > 0 ? undefined : 5,
                        forceNiceScale: true,
                        title: {
                            text: series.total || 'Reseñas',
                            style: {
                                color: valueColor,
                                fontSize: '11px',
                                fontWeight: 600,
                            },
                        },
                        labels: {
                            style: {
                                colors: valueColor,
                                fontSize: '11px',
                            },
                            formatter: (value) => Math.round(Number(value)).toLocaleString(),
                        },
                    },
                    grid: {
                        borderColor: gridColor,
                        strokeDashArray: 0,
                        padding: { top: 6, right: 36, bottom: 4, left: 8 },
                    },
                    tooltip: {
                        marker: { show: false },
                        y: {
                            formatter: (value) => `${Math.round(Number(value)).toLocaleString()} ${series.total || ''}`.trim(),
                        },
                    },
                    noData: {
                        text: config.empty_label || '',
                        align: 'center',
                        verticalAlign: 'middle',
                        style: { color: valueColor, fontSize: '13px' },
                    },
                });

                await chartElement._reputalisChart.render();
                card._chartSignature = signature;
            } finally {
                card._renderingSignature = null;
            }
        };

        const renderStarsChart = async (card, config) => {
            const chartElement = card.querySelector('[data-external-reputation-chart="stars"]');
            if (!chartElement) {
                return;
            }

            const signature = JSON.stringify({ type: 'stars', config });
            if (card._renderingSignature === signature) {
                return;
            }
            if (card._chartSignature === signature && hasRenderedChart(chartElement)) {
                return;
            }
            if (!isVisibleForRender(chartElement) && !isVisibleForRender(card)) {
                queueRetry();
                return;
            }

            card._renderingSignature = signature;

            try {
                const ApexCharts = await loadApexCharts();
                await new Promise((resolve) => window.requestAnimationFrame(resolve));

                if (!isVisibleForRender(chartElement) && !isVisibleForRender(card)) {
                    queueRetry();
                    return;
                }

                destroyChart(chartElement);

                const labels = config.labels || [];
                const { valueColor, gridColor } = theme();
                const series = config.series || {};
                const xLabelRotate = labels.length > 12 ? -35 : 0;

                chartElement._reputalisChart = new ApexCharts(chartElement, {
                    chart: {
                        type: 'area',
                        height: 260,
                        stacked: true,
                        stackType: 'normal',
                        parentHeightOffset: 0,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        animations: { enabled: false },
                        background: 'transparent',
                    },
                    series: [
                        { name: series.stars_1 || '1★', data: config.stars_1 || [] },
                        { name: series.stars_2 || '2★', data: config.stars_2 || [] },
                        { name: series.stars_3 || '3★', data: config.stars_3 || [] },
                        { name: series.stars_4 || '4★', data: config.stars_4 || [] },
                        { name: series.stars_5 || '5★', data: config.stars_5 || [] },
                    ],
                    colors: ['#FF3901', '#FF9880', '#FFC60F', '#8DFFA8', '#01FF01'],
                    stroke: {
                        curve: 'smooth',
                        width: 2,
                    },
                    fill: {
                        type: 'solid',
                        opacity: 0.72,
                    },
                    markers: { size: 0 },
                    dataLabels: { enabled: false },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                        labels: { colors: valueColor },
                    },
                    xaxis: {
                        categories: labels,
                        labels: {
                            rotate: xLabelRotate,
                            hideOverlappingLabels: true,
                            trim: true,
                            style: {
                                colors: valueColor,
                                fontSize: '11px',
                                fontWeight: 500,
                            },
                        },
                        axisBorder: { color: gridColor },
                        axisTicks: { show: false },
                        tooltip: { enabled: false },
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        title: {
                            text: series.total || 'Reseñas',
                            style: {
                                color: valueColor,
                                fontSize: '11px',
                                fontWeight: 600,
                            },
                        },
                        labels: {
                            style: {
                                colors: valueColor,
                                fontSize: '11px',
                            },
                            formatter: (value) => Math.round(Number(value)).toLocaleString(),
                        },
                    },
                    grid: {
                        borderColor: gridColor,
                        strokeDashArray: 0,
                        padding: { top: 6, right: 36, bottom: 4, left: 8 },
                    },
                    tooltip: {
                        shared: true,
                        marker: { show: true },
                    },
                    noData: {
                        text: config.empty_label || '',
                        align: 'center',
                        verticalAlign: 'middle',
                        style: { color: valueColor, fontSize: '13px' },
                    },
                });

                await chartElement._reputalisChart.render();
                card._chartSignature = signature;
            } finally {
                card._renderingSignature = null;
            }
        };

        const renderCard = async (card) => {
            const config = parseConfig(card);
            if (!config) {
                return;
            }

            const type = card.getAttribute('data-external-reputation-chart-card');
            if (type === 'rating') {
                await renderRatingChart(card, config);
            } else if (type === 'total') {
                await renderTotalChart(card, config);
            } else if (type === 'stars') {
                await renderStarsChart(card, config);
            }
        };

        window.reputalisInitExternalReputationCharts = () => {
            document.querySelectorAll('[data-external-reputation-chart-card]').forEach((card) => {
                renderCard(card).catch(() => {
                    queueRetry(200);
                });
            });
        };

        document.addEventListener('DOMContentLoaded', window.reputalisInitExternalReputationCharts);
        document.addEventListener('livewire:navigated', window.reputalisInitExternalReputationCharts);
        window.addEventListener('load', () => queueRetry(60));
        window.addEventListener('resize', () => queueRetry(120));

        if (window.Livewire?.hook) {
            window.Livewire.hook('morphed', () => queueRetry(80));
        }

        window.reputalisInitExternalReputationCharts();
        [80, 200, 500].forEach((delay) => {
            window.setTimeout(() => window.reputalisInitExternalReputationCharts?.(), delay);
        });
    })();
</script>
