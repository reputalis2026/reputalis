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

        const fillSeriesForward = (values) => {
            const filled = values.slice();
            let lastKnown = null;
            for (let i = 0; i < filled.length; i++) {
                if (filled[i] !== null && Number.isFinite(filled[i])) {
                    lastKnown = filled[i];
                } else if (lastKnown !== null) {
                    filled[i] = lastKnown;
                }
            }

            return filled;
        };

        const formatChartNumber = (value, digits, locale) => {
            if (value === null || value === undefined || ! Number.isFinite(Number(value))) {
                return '';
            }

            const numeric = digits === 0 ? Math.round(Number(value)) : Number(value);

            return numeric.toLocaleString(locale || document.documentElement.lang || 'es', {
                minimumFractionDigits: digits,
                maximumFractionDigits: digits,
            });
        };

        const niceAxisStep = (raw) => {
            if (! Number.isFinite(raw) || raw <= 0) {
                return 1;
            }

            const magnitude = Math.pow(10, Math.floor(Math.log10(raw)));
            const residual = raw / magnitude;
            if (residual <= 1) {
                return magnitude;
            }
            if (residual <= 2) {
                return 2 * magnitude;
            }
            if (residual <= 5) {
                return 5 * magnitude;
            }

            return 10 * magnitude;
        };

        const buildClientEvolutionOptions = ({
            labels,
            values,
            counts = [],
            badgeValue = null,
            height = 240,
            accent = '#2eb5d6',
            yCeiling = 5,
            yDecimals = 1,
            badgeDecimals = 2,
            badgeSuffix = '',
            badgeBackground = '#1e293b',
            emptyLabel = '',
            locale,
            granularity = 'month',
            tightScale = false,
        }) => {
            const isMobileChart = window.innerWidth < 768;
            const axisColor = '#8a9ea4';
            const hasEvents = counts.length === 0 || counts.some((count) => Number(count) > 0);
            const seriesValues = hasEvents ? values : values.map(() => null);
            const plotValues = fillSeriesForward(seriesValues);
            const numericPlotValues = plotValues.filter((value) => value !== null && Number.isFinite(value));
            const realCount = values.filter((value) => value !== null && Number.isFinite(value)).length;
            const showPointMarkers = realCount > 0 && realCount <= 16;
            let yMin = 0;
            let yMax = yCeiling;
            let yTickAmount = 5;

            if (numericPlotValues.length) {
                const dataMin = Math.min(...numericPlotValues);
                const dataMax = Math.max(...numericPlotValues);
                if (tightScale && yCeiling <= 5) {
                    const step = yDecimals >= 2 ? 0.05 : 0.1;
                    const pad = yDecimals >= 2 ? 0.02 : 0.05;
                    yMin = Math.max(0, Math.floor((dataMin - pad) / step) * step);
                    yMax = Math.min(yCeiling, Math.ceil((dataMax + pad) / step) * step);
                    if (yMax - yMin < step * 3) {
                        yMin = Math.max(0, +(yMax - step * 3).toFixed(2));
                    }
                    if (yMax <= yMin) {
                        yMax = Math.min(yCeiling, +(yMin + step * 3).toFixed(2));
                    }
                    yTickAmount = Math.max(2, Math.round((yMax - yMin) / step));
                } else if (yCeiling <= 5) {
                    yMin = Math.max(0, Math.floor((dataMin - 0.05) * 2) / 2);
                    yMax = Math.min(yCeiling, Math.ceil((dataMax + 0.1) * 2) / 2);
                    if (yMax - yMin < 1.5) {
                        yMin = Math.max(0, Math.round((yMax - 1.5) * 2) / 2);
                    }
                    if (yMax <= yMin) {
                        yMax = Math.min(yCeiling, yMin + 1.5);
                    }
                    yTickAmount = Math.max(2, Math.round((yMax - yMin) / 0.5));
                } else {
                    const maxTicks = isMobileChart ? 3 : 4;
                    const spread = Math.max(dataMax - dataMin, 1);
                    let step = niceAxisStep((spread * 1.5) / maxTicks);
                    yMin = Math.max(0, Math.floor((dataMin - step * 0.3) / step) * step);
                    yMax = Math.ceil((dataMax + step * 0.3) / step) * step;
                    if (yMax <= yMin) {
                        yMax = yMin + step * maxTicks;
                    }
                    yTickAmount = Math.max(2, Math.round((yMax - yMin) / step));
                    if (yTickAmount > maxTicks) {
                        step = niceAxisStep((yMax - yMin) / maxTicks);
                        yMin = Math.max(0, Math.floor((dataMin - step * 0.2) / step) * step);
                        yTickAmount = maxTicks;
                        yMax = yMin + step * yTickAmount;
                        if (yMax < dataMax) {
                            yMax = Math.ceil((dataMax + step * 0.2) / step) * step;
                            yMin = Math.max(0, yMax - step * maxTicks);
                        }
                    }
                }

                if (isMobileChart) {
                    yTickAmount = Math.min(yTickAmount, 4);
                }
            }

            let lastIndex = -1;
            for (let i = plotValues.length - 1; i >= 0; i--) {
                if (plotValues[i] !== null && Number.isFinite(plotValues[i])) {
                    lastIndex = i;
                    break;
                }
            }

            const resolvedBadge = badgeValue !== null && badgeValue !== undefined && Number.isFinite(Number(badgeValue))
                ? Number(badgeValue)
                : (lastIndex >= 0 ? plotValues[lastIndex] : null);
            const axisSourceLabels = labels.map((label) => String(label).split(' · ')[0]);
            const categoryKeys = axisSourceLabels.map((label, index) => `${index}:${label}`);
            const staggerMonthLabels = granularity === 'month' && axisSourceLabels.length >= 10;
            const maxTicks = (() => {
                const count = axisSourceLabels.length;
                if (granularity === 'month') {
                    return null;
                }
                if (isMobileChart && granularity === 'day' && count >= 7) {
                    return 4;
                }
                if (granularity === 'day' && count > 8) {
                    return 8;
                }

                return null;
            })();
            const visibleTickIndexes = (() => {
                if (! maxTicks || axisSourceLabels.length <= maxTicks) {
                    return null;
                }

                const indexes = new Set([0, axisSourceLabels.length - 1]);
                const inner = maxTicks - 2;
                for (let i = 1; i <= inner; i++) {
                    indexes.add(Math.round((i * (axisSourceLabels.length - 1)) / (inner + 1)));
                }

                return indexes;
            })();
            const axisLabels = axisSourceLabels.map((label, index) => {
                if (visibleTickIndexes && ! visibleTickIndexes.has(index)) {
                    return '';
                }

                if (staggerMonthLabels) {
                    return index % 2 === 1 ? `\n${label}` : `${label}\n`;
                }

                return label;
            });
            const markerSize = isMobileChart ? 5 : 6;
            const yLabelSample = formatChartNumber(yMax, yDecimals, locale);
            const yLabelWidth = Math.min(76, Math.max(
                yDecimals === 0 ? 52 : 36,
                String(yLabelSample).length * (isMobileChart ? 6.4 : 7) + 14
            ));

            return {
                chart: {
                    type: 'area',
                    height,
                    parentHeightOffset: 8,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: { enabled: false },
                    dropShadow: { enabled: false },
                },
                series: [{
                    name: '',
                    data: plotValues.map((value, index) => ({ x: categoryKeys[index], y: value })),
                }],
                colors: [accent],
                stroke: { curve: 'straight', width: 2.75, connectNulls: true },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 0.2,
                        opacityFrom: 0.45,
                        opacityTo: 0.06,
                        stops: [0, 80, 100],
                    },
                },
                markers: {
                    size: 0,
                    strokeWidth: 3,
                    strokeColors: accent,
                    colors: ['#ffffff'],
                    hover: { sizeOffset: 1 },
                    discrete: plotValues.map((value, index) => ({
                        seriesIndex: 0,
                        dataPointIndex: index,
                        size: (
                            showPointMarkers
                            && value !== null
                            && Number.isFinite(value)
                            && (index === lastIndex || (counts[index] || 0) > 0)
                        ) ? markerSize : 0,
                        fillColor: '#ffffff',
                        strokeColor: accent,
                        strokeWidth: 3,
                    })),
                },
                annotations: lastIndex >= 0 && resolvedBadge !== null ? {
                    points: [{
                        x: categoryKeys[lastIndex],
                        y: plotValues[lastIndex],
                        marker: { size: 0 },
                        label: {
                            text: `${formatChartNumber(resolvedBadge, badgeDecimals, locale)}${badgeSuffix}`,
                            offsetY: isMobileChart ? -14 : -12,
                            offsetX: isMobileChart ? -8 : 6,
                            borderWidth: 0,
                            borderRadius: 8,
                            style: {
                                background: badgeBackground,
                                color: '#ffffff',
                                fontSize: isMobileChart ? '10px' : '11px',
                                fontWeight: 700,
                                padding: { left: 8, right: 8, top: 3, bottom: 3 },
                            },
                        },
                    }],
                } : {},
                dataLabels: { enabled: false },
                xaxis: {
                    type: 'category',
                    categories: categoryKeys,
                    overwriteCategories: axisLabels,
                    tickPlacement: 'on',
                    labels: {
                        show: true,
                        rotate: 0,
                        rotateAlways: false,
                        hideOverlappingLabels: false,
                        trim: false,
                        minHeight: 32,
                        offsetY: staggerMonthLabels ? 2 : 6,
                        style: {
                            colors: axisColor,
                            fontSize: staggerMonthLabels ? '9px' : (isMobileChart ? '10px' : '11px'),
                            fontWeight: 500,
                        },
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    tooltip: { enabled: false },
                },
                yaxis: {
                    min: yMin,
                    max: yMax,
                    tickAmount: yTickAmount,
                    forceNiceScale: false,
                    decimalsInFloat: yDecimals,
                    floating: false,
                    title: { text: undefined },
                    labels: {
                        show: true,
                        minWidth: yLabelWidth,
                        maxWidth: yLabelWidth + 8,
                        offsetX: 0,
                        padding: 4,
                        style: { colors: axisColor, fontSize: isMobileChart ? '10px' : '11px' },
                        formatter: (value) => {
                            if (! Number.isFinite(Number(value))) {
                                return '';
                            }

                            const numeric = Number(value);
                            const threshold = yDecimals === 0 ? 0.51 : (yDecimals >= 2 ? 0.006 : 0.021);
                            if (numeric <= yMin + threshold) {
                                return '';
                            }

                            return formatChartNumber(numeric, yDecimals, locale);
                        },
                    },
                },
                grid: {
                    borderColor: 'rgba(18, 53, 60, 0.08)',
                    strokeDashArray: 0,
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } },
                    padding: {
                        top: 22,
                        right: isMobileChart ? 18 : 26,
                        bottom: 22,
                        left: 2,
                    },
                },
                tooltip: { enabled: false },
                noData: {
                    text: emptyLabel,
                    align: 'center',
                    verticalAlign: 'middle',
                    style: { color: '#334155', fontSize: '13px' },
                },
            };
        };

        const renderClientEvolutionChart = async (card) => {
            const config = parseConfig(card);
            if (!config) {
                return;
            }

            const chartElement = card.querySelector('[data-external-reputation-chart]');
            if (!chartElement) {
                return;
            }

            const signature = JSON.stringify(config);
            if (card._renderingSignature === signature) {
                return;
            }
            if (card._chartSignature === signature && hasRenderedChart(chartElement)) {
                return;
            }

            card._renderingSignature = signature;

            try {
                const ApexCharts = await loadApexCharts();
                await new Promise((resolve) => window.requestAnimationFrame(resolve));

                destroyChart(chartElement);

                chartElement._reputalisChart = new ApexCharts(chartElement, buildClientEvolutionOptions({
                    labels: config.labels || [],
                    values: (config.values || []).map((value) => value === null ? null : Number(value)),
                    counts: config.counts || [],
                    badgeValue: config.overallAverage,
                    height: Math.min(260, Math.max(200, chartElement.clientHeight || 240)),
                    accent: config.accent || '#2eb5d6',
                    yCeiling: config.yCeiling || 5,
                    yDecimals: config.yDecimals ?? 1,
                    badgeDecimals: config.badgeDecimals ?? 1,
                    badgeBackground: config.badgeBackground || '#1e293b',
                    emptyLabel: config.emptyLabel || '',
                    locale: config.locale,
                    granularity: config.granularity || 'month',
                    tightScale: Boolean(config.tightScale),
                }));

                await chartElement._reputalisChart.render();
                card._chartSignature = signature;
            } finally {
                card._renderingSignature = null;
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
                    colors: ['#EE2737', '#FF6A13', '#FFB81C', '#A4D65E', '#00B140'],
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

        const renderSingleGauge = async (chartElement, config, cacheKey) => {
            const card = chartElement.closest('[data-external-reputation-gauge-card]');
            if (!card) return;

            const signature = JSON.stringify({ type: cacheKey, config });
            if (chartElement._gaugeRenderingSig === signature) return;
            if (chartElement._gaugeSig === signature && hasRenderedChart(chartElement)) return;
            if (!isVisibleForRender(chartElement) && !isVisibleForRender(card)) { queueRetry(); return; }

            chartElement._gaugeRenderingSig = signature;

            try {
                const ApexCharts = await loadApexCharts();
                await new Promise((resolve) => window.requestAnimationFrame(resolve));
                if (!isVisibleForRender(chartElement) && !isVisibleForRender(card)) { queueRetry(); return; }

                destroyChart(chartElement);
                const { valueColor } = theme();

                chartElement._reputalisChart = new ApexCharts(chartElement, {
                    chart: {
                        type: 'radialBar',
                        height: 150,
                        parentHeightOffset: 0,
                        sparkline: { enabled: true },
                    },
                    series: [Number(config.gaugePercent || 0)],
                    colors: [config.gaugeColor || '#9ca3af'],
                    plotOptions: {
                        radialBar: {
                            hollow: { size: '55%' },
                            track: {
                                background: config.trackColor || '#e5e7eb',
                                strokeWidth: '100%',
                            },
                            dataLabels: {
                                show: true,
                                name: {
                                    show: true,
                                    offsetY: 20,
                                    color: config.labelColor || '#6b7280',
                                    fontSize: '9px',
                                    fontWeight: 500,
                                },
                                value: {
                                    show: true,
                                    offsetY: -6,
                                    color: valueColor,
                                    fontSize: '22px',
                                    fontWeight: 700,
                                    formatter: () => config.gaugeValue || '',
                                },
                            },
                        },
                    },
                    labels: [config.gaugeLabel || ''],
                    stroke: { lineCap: 'round' },
                });

                await chartElement._reputalisChart.render();
                chartElement._gaugeSig = signature;
            } finally {
                chartElement._gaugeRenderingSig = null;
            }
        };

        const renderGaugeChart = async () => {
            const card = document.querySelector('[data-external-reputation-gauge-card]');
            if (!card) return;

            for (const key of ['google', 'real']) {
                const chartEl = card.querySelector(`[data-external-reputation-chart="gauge-${key}"]`);
                const configNode = card.querySelector(`[data-external-reputation-gauge-config="${key}"]`);
                if (!chartEl || !configNode) continue;

                let config;
                try { config = JSON.parse(configNode.textContent || '{}'); } catch (_e) { continue; }
                await renderSingleGauge(chartEl, config, `gauge-${key}`);
            }
        };

        const lightenHexColor = (hex, ratio = 0.38) => {
            const normalized = String(hex || '').replace('#', '');
            if (normalized.length !== 6) return '#f3f4f6';
            const channels = [0, 2, 4].map((start) => parseInt(normalized.substring(start, start + 2), 16));
            const mixed = channels.map((ch) => Math.round(ch + (255 - ch) * ratio));
            return `#${mixed.map((ch) => ch.toString(16).padStart(2, '0')).join('')}`;
        };

        const defaultScoreColors = ['#EE2737', '#FF6A13', '#FFB81C', '#A4D65E', '#00B140'];

        const renderBreakdownChart = async (card) => {
            const chartElement = card.querySelector('[data-external-reputation-chart="breakdown"]');
            if (!chartElement) {
                return;
            }

            const configNode = card.querySelector('[data-external-reputation-breakdown-config]');
            if (!configNode) {
                return;
            }

            let config;
            try {
                config = JSON.parse(configNode.textContent || '{}');
            } catch (_e) {
                return;
            }

            const signature = JSON.stringify({ type: 'breakdown', config });
            if (card._breakdownRenderingSignature === signature) {
                return;
            }
            if (card._breakdownSignature === signature && hasRenderedChart(chartElement)) {
                return;
            }
            if (!isVisibleForRender(chartElement) && !isVisibleForRender(card)) {
                queueRetry();
                return;
            }

            card._breakdownRenderingSignature = signature;

            try {
                const ApexCharts = await loadApexCharts();
                await new Promise((resolve) => window.requestAnimationFrame(resolve));

                if (!isVisibleForRender(chartElement) && !isVisibleForRender(card)) {
                    queueRetry();
                    return;
                }

                destroyChart(chartElement);

                const breakdownData = (config.scoreLabels || [])
                    .map((label, index) => ({
                        score: Number(label),
                        percentage: Number((config.scorePercentages || [])[index] || 0),
                        count: Number((config.scoreCounts || [])[index] || 0),
                    }))
                    .sort((a, b) => a.score - b.score);

                const chartWidth = chartElement.clientWidth || 400;
                const compactLabels = chartWidth < 300;
                const veryCompactLabels = chartWidth < 220;

                chartElement._reputalisChart = new ApexCharts(chartElement, {
                    chart: {
                        type: 'bar',
                        height: '100%',
                        width: '100%',
                        parentHeightOffset: 0,
                        toolbar: { show: false },
                        animations: { enabled: false },
                    },
                    series: [{
                        name: '',
                        data: breakdownData.map((item) => item.percentage),
                    }],
                    colors: config.scoreColors?.length ? config.scoreColors : defaultScoreColors,
                    legend: { show: false },
                    plotOptions: {
                        bar: {
                            distributed: true,
                            horizontal: false,
                            borderRadius: 5,
                            columnWidth: veryCompactLabels ? '58%' : compactLabels ? '64%' : '72%',
                        },
                    },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: breakdownData.map((item) => `${item.score} ⭐`),
                        labels: {
                            rotate: veryCompactLabels ? -45 : compactLabels ? -35 : 0,
                            rotateAlways: veryCompactLabels || compactLabels,
                            hideOverlappingLabels: true,
                            trim: true,
                            style: {
                                fontSize: veryCompactLabels ? '9px' : compactLabels ? '10px' : '11px',
                                fontWeight: 600,
                                colors: config.labelColor || '#6b7280',
                            },
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                    },
                    yaxis: {
                        min: 0,
                        max: 100,
                        tickAmount: 4,
                        labels: { show: false },
                    },
                    grid: {
                        show: false,
                        padding: { top: 36, right: 4, bottom: 0, left: 4 },
                    },
                    tooltip: {
                        theme: false,
                        cssClass: 'reputalis-breakdown-tooltip',
                        followCursor: false,
                        shared: false,
                        intersect: true,
                        offsetY: -10,
                        fixed: {
                            enabled: true,
                            position: 'topLeft',
                            offsetX: 8,
                            offsetY: 4,
                        },
                        custom: ({ dataPointIndex }) => {
                            const item = breakdownData[dataPointIndex] || { count: 0 };
                            const label = config.surveysTooltipLabel || 'Nº de reseñas:';
                            const barColor = (config.scoreColors || defaultScoreColors)[dataPointIndex] || defaultScoreColors[0];
                            const bg = lightenHexColor(barColor, 0.4);
                            return '<div style="background:' + bg + ';color:#fff;border:1px solid rgba(15,23,42,.08);border-radius:.5rem;box-shadow:0 4px 12px rgba(15,23,42,.12);font-size:.8125rem;font-weight:650;line-height:1.25rem;padding:.45rem .65rem;white-space:nowrap;">' + label + ' ' + item.count + '</div>';
                        },
                    },
                });

                await chartElement._reputalisChart.render();
                card._breakdownSignature = signature;
            } finally {
                card._breakdownRenderingSignature = null;
            }
        };

        const renderCard = async (card) => {
            const type = card.getAttribute('data-external-reputation-chart-card');

            if (type && type.startsWith('client-')) {
                await renderClientEvolutionChart(card);
                return;
            }

            if (type === 'breakdown') {
                await renderBreakdownChart(card);
                return;
            }

            const config = parseConfig(card);
            if (!config) {
                return;
            }

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
                const chartElement = card.querySelector('[data-external-reputation-chart]');
                if (chartElement && ! hasRenderedChart(chartElement)) {
                    card._chartSignature = null;
                    card._renderingSignature = null;
                }
            });

            renderGaugeChart().catch(() => queueRetry(200));

            document.querySelectorAll('[data-external-reputation-chart-card]').forEach((card) => {
                renderCard(card).catch(() => {
                    queueRetry(200);
                });
            });
        };

        const bindLivewireChartRefresh = () => {
            if (! window.Livewire || window.reputalisExternalChartsLivewireBound) {
                return;
            }

            window.reputalisExternalChartsLivewireBound = true;

            window.Livewire.hook?.('morphed', () => queueRetry(80));
            window.Livewire.hook?.('commit', ({ succeed }) => {
                succeed?.(() => queueRetry(80));
            });
            window.Livewire.on?.('reputalis-external-charts-refresh', () => {
                queueRetry(30);
                queueRetry(160);
                queueRetry(400);
            });
        };

        document.addEventListener('DOMContentLoaded', window.reputalisInitExternalReputationCharts);
        document.addEventListener('livewire:navigated', window.reputalisInitExternalReputationCharts);
        document.addEventListener('livewire:init', bindLivewireChartRefresh);
        window.addEventListener('load', () => queueRetry(60));
        window.addEventListener('resize', () => queueRetry(120));
        bindLivewireChartRefresh();

        window.reputalisInitExternalReputationCharts();
        [80, 200, 500].forEach((delay) => {
            window.setTimeout(() => window.reputalisInitExternalReputationCharts?.(), delay);
        });
    })();
</script>
