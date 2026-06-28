<script>
    (() => {
        if (window.reputalisClientDashboardChartsReady) {
            window.reputalisInitClientDashboardCharts?.();
            return;
        }

        window.reputalisClientDashboardChartsReady = true;

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
                script.defer = true;
                script.dataset.reputalisApexcharts = 'true';
                script.onload = () => resolve(window.ApexCharts);
                script.onerror = (error) => {
                    window.reputalisApexChartsPromise = null;
                    reject(error);
                };
                document.head.appendChild(script);
            });

            return window.reputalisApexChartsPromise;
        };

        const parseJsonConfig = (card, selector) => {
            const configNode = card.querySelector(selector);
            if (!configNode) {
                return null;
            }

            try {
                return JSON.parse(configNode.textContent || '{}');
            } catch (error) {
                console.error('Invalid dashboard chart config', error);
                return null;
            }
        };

        const parseConfig = (card) => parseJsonConfig(card, '[data-dashboard-summary-config]');

        const destroyChart = (element) => {
            if (element?._reputalisChart) {
                element._reputalisChart.destroy();
                element._reputalisChart = null;
                element.innerHTML = '';
            }
        };

        const hasRenderedChart = (chartElement) => {
            return Boolean(
                chartElement?._reputalisChart &&
                chartElement.querySelector('.apexcharts-canvas'),
            );
        };

        const resetStaleDashboardChartSignatures = () => {
            document.querySelectorAll('[data-dashboard-summary-chart]').forEach((card) => {
                const gauge = card.querySelector('[data-dashboard-chart="gauge"]');
                const satisfied = card.querySelector('[data-dashboard-chart="satisfied"]');
                const breakdown = card.querySelector('[data-dashboard-chart="breakdown"]');

                if (
                    card._reputalisChartsSignature &&
                    (! hasRenderedChart(gauge) || ! hasRenderedChart(satisfied) || ! hasRenderedChart(breakdown))
                ) {
                    card._reputalisChartsSignature = null;
                    card._reputalisChartsRenderingSignature = null;
                }
            });

            document.querySelectorAll('[data-dashboard-history-chart]').forEach((card) => {
                const chartElement = card.querySelector('[data-dashboard-chart="survey-history"]');

                if (card._reputalisHistoryChartSignature && ! hasRenderedChart(chartElement)) {
                    card._reputalisHistoryChartSignature = null;
                    card._reputalisHistoryChartRenderingSignature = null;
                }
            });

            document.querySelectorAll('[data-dashboard-trend-chart]').forEach((card) => {
                const chartElement = card.querySelector('[data-dashboard-chart="score-trend"]');

                if (card._reputalisTrendChartSignature && ! hasRenderedChart(chartElement)) {
                    card._reputalisTrendChartSignature = null;
                    card._reputalisTrendChartRenderingSignature = null;
                }
            });

            document.querySelectorAll('[data-dashboard-employee-detail]').forEach((modal) => {
                const trendChartElement = modal.querySelector('[data-dashboard-chart="employee-trend"]');
                const satisfiedChartElement = modal.querySelector('[data-dashboard-chart="employee-satisfied"]');

                if (modal._reputalisEmployeeTrendChartSignature && ! hasRenderedChart(trendChartElement)) {
                    modal._reputalisEmployeeTrendChartSignature = null;
                    modal._reputalisEmployeeTrendChartRenderingSignature = null;
                }

                if (modal._reputalisEmployeeSatisfiedChartSignature && ! hasRenderedChart(satisfiedChartElement)) {
                    modal._reputalisEmployeeSatisfiedChartSignature = null;
                    modal._reputalisEmployeeSatisfiedChartRenderingSignature = null;
                }
            });

            document.querySelectorAll('[data-dashboard-improvement-detail]').forEach((modal) => {
                const chartElement = modal.querySelector('[data-dashboard-chart="improvement-detail"]');

                if (modal._reputalisImprovementDetailChartSignature && ! hasRenderedChart(chartElement)) {
                    modal._reputalisImprovementDetailChartSignature = null;
                    modal._reputalisImprovementDetailChartRenderingSignature = null;
                }
            });
        };

        const isVisibleForRender = (element) => {
            if (!element?.isConnected) {
                return false;
            }

            const rect = element.getBoundingClientRect();

            return rect.width > 20;
        };

        const queueDashboardChartsRetry = (delay = 120) => {
            window.clearTimeout(window.reputalisDashboardChartsRetryTimer);
            window.reputalisDashboardChartsRetryTimer = window.setTimeout(() => {
                window.reputalisInitClientDashboardCharts?.();
            }, delay);
        };

        const lightenHexColor = (hex, ratio = 0.38) => {
            const normalized = String(hex || '').replace('#', '');

            if (normalized.length !== 6) {
                return '#f3f4f6';
            }

            const channels = [0, 2, 4].map((start) => parseInt(normalized.substring(start, start + 2), 16));
            const mixed = channels.map((channel) => Math.round(channel + (255 - channel) * ratio));

            return `#${mixed.map((channel) => channel.toString(16).padStart(2, '0')).join('')}`;
        };

        const defaultScoreColors = ['#FF3901', '#FF9880', '#FFC60F', '#8DFFA8', '#01FF01'];

        const buildBreakdownTooltip = (config, breakdownData) => ({
            theme: false,
            cssClass: 'reputalis-breakdown-tooltip',
            custom: ({ dataPointIndex }) => {
                const item = breakdownData[dataPointIndex] || { count: 0 };
                const label = config.surveysTooltipLabel || 'Número de encuestas:';
                const barColor = (config.scoreColors || defaultScoreColors)[dataPointIndex] || defaultScoreColors[0];
                const backgroundColor = lightenHexColor(barColor, 0.4);

                return '<' + 'div style="'
                    + 'background:' + backgroundColor + ';'
                    + 'color:#ffffff;'
                    + 'border:1px solid rgba(15, 23, 42, .08);'
                    + 'border-radius:.5rem;'
                    + 'box-shadow:0 4px 12px rgba(15, 23, 42, .12);'
                    + 'font-size:.8125rem;'
                    + 'font-weight:650;'
                    + 'line-height:1.25rem;'
                    + 'padding:.45rem .65rem;'
                    + 'white-space:nowrap;'
                    + '">' + label + ' ' + item.count + '<' + '/' + 'div' + '>';
            },
        });

        const clampChartSize = (value, min, max) => Math.round(Math.min(max, Math.max(min, value)));

        const formatHourLabel = (hour) => `${String(hour).padStart(2, '0')}:00`;

        const MOBILE_HOURLY_MAX_WIDTH = 768;
        const HOUR_SHIFT_DAY = 'day';
        const HOUR_SHIFT_NIGHT = 'night';

        const isMobileHourlyViewport = () => window.matchMedia(`(max-width: ${MOBILE_HOURLY_MAX_WIDTH}px)`).matches;

        const getDefaultHourShift = () => (new Date().getHours() < 12 ? HOUR_SHIFT_DAY : HOUR_SHIFT_NIGHT);

        const sliceHourlySeries = (labels, series, shift) => {
            const start = shift === HOUR_SHIFT_NIGHT ? 12 : 0;

            return {
                labels: (labels || []).slice(start, start + 12),
                series: (series || []).slice(start, start + 12),
                hourOffset: start,
            };
        };

        const resolveHourShift = () => {
            if (!window.reputalisSharedHourShift) {
                window.reputalisSharedHourShift = getDefaultHourShift();
            }

            return window.reputalisSharedHourShift;
        };

        const syncHourShiftControls = (card, shift) => {
            const controls = card.querySelector('[data-hour-shift-controls]');
            if (!controls) {
                return;
            }

            controls.querySelectorAll('[data-hour-shift]').forEach((button) => {
                const isActive = button.dataset.hourShift === shift;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        const bindHourShiftControls = () => {
            if (window.reputalisHourShiftControlsBound) {
                return;
            }

            window.reputalisHourShiftControlsBound = true;

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-hour-shift]');
                if (!button) {
                    return;
                }

                const card = button.closest('[data-dashboard-history-chart], [data-dashboard-trend-chart]');
                if (!card || !isMobileHourlyViewport()) {
                    return;
                }

                const shift = button.dataset.hourShift;
                if (!shift || window.reputalisSharedHourShift === shift) {
                    return;
                }

                window.reputalisSharedHourShift = shift;
                resetDashboardHourlyChartState();
                window.reputalisInitClientDashboardCharts?.();
            });
        };

        const resetDashboardHourlyChartState = () => {
            document
                .querySelectorAll('[data-dashboard-history-chart], [data-dashboard-trend-chart]')
                .forEach((card) => {
                    card._reputalisHistoryChartSignature = null;
                    card._reputalisHistoryChartRenderingSignature = null;
                    card._reputalisTrendChartSignature = null;
                    card._reputalisTrendChartRenderingSignature = null;
                });
        };

        const syncDashboardRangeKey = (config) => {
            const rangeKey = config?.rangeKey;

            if (!rangeKey) {
                return;
            }

            if (window.reputalisDashboardRangeKey && window.reputalisDashboardRangeKey !== rangeKey) {
                window.reputalisSharedHourShift = null;
                resetDashboardHourlyChartState();
            }

            window.reputalisDashboardRangeKey = rangeKey;
        };

        const resolveMainSummaryChartSizes = (card) => {
            const gauge = card.querySelector('[data-dashboard-chart="gauge"]');
            const breakdown = card.querySelector('[data-dashboard-chart="breakdown"]');
            const radialShell = gauge?.closest('.client-dashboard-main-summary-chart-shell');
            const radialSize = clampChartSize(radialShell?.clientWidth || gauge?.clientWidth || 160, 100, 180);
            const breakdownWidth = breakdown?.clientWidth || 0;
            const breakdownHeight = clampChartSize(
                breakdown?.clientHeight || breakdown?.parentElement?.clientHeight || 170,
                140,
                190,
            );

            return { radialSize, breakdownWidth, breakdownHeight };
        };

        const buildGaugeRadialOptions = (config, valueColor, chartHeight = 180) => ({
            chart: {
                type: 'radialBar',
                height: chartHeight,
                parentHeightOffset: 0,
                sparkline: { enabled: true },
            },
            series: [Number(config.gaugePercent || 0)],
            colors: [config.gaugeColor || '#9ca3af'],
            plotOptions: {
                radialBar: {
                    hollow: { size: chartHeight >= 150 ? '58%' : '52%' },
                    track: {
                        background: config.trackColor || '#e5e7eb',
                        strokeWidth: '100%',
                    },
                    dataLabels: {
                        show: true,
                        name: {
                            show: true,
                            offsetY: chartHeight >= 150 ? 24 : 18,
                            color: config.labelColor || '#6b7280',
                            fontSize: chartHeight >= 150 ? '10px' : '9px',
                            fontWeight: 500,
                        },
                        value: {
                            show: true,
                            offsetY: chartHeight >= 150 ? -8 : -6,
                            color: valueColor,
                            fontSize: chartHeight >= 180 ? '28px' : chartHeight >= 140 ? '22px' : chartHeight >= 110 ? '18px' : '16px',
                            fontWeight: 700,
                            formatter: () => config.gaugeValue || '',
                        },
                    },
                },
            },
            labels: [config.gaugeLabel || ''],
            stroke: { lineCap: 'round' },
        });

        const buildBreakdownChartOptions = (config, breakdownData, { breakdownWidth, breakdownHeight }) => {
            const compactLabels = breakdownWidth > 0 && breakdownWidth < 300;
            const veryCompactLabels = breakdownWidth > 0 && breakdownWidth < 220;

            return {
                chart: {
                    type: 'bar',
                    height: breakdownHeight,
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
                dataLabels: {
                    enabled: false,
                },
                xaxis: {
                    categories: breakdownData.map((item) => `${item.percentage}%`),
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
                    show: true,
                    borderColor: 'rgba(148, 163, 184, 0.18)',
                    strokeDashArray: 3,
                    padding: {
                        top: 10,
                        right: veryCompactLabels ? 4 : 0,
                        bottom: veryCompactLabels ? -2 : compactLabels ? -4 : -6,
                        left: veryCompactLabels ? 4 : 0,
                    },
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } },
                },
                tooltip: buildBreakdownTooltip(config, breakdownData),
            };
        };

        const buildSatisfiedRadialOptions = (config, valueColor, chartHeight = 180, { compact = false } = {}) => ({
            chart: {
                type: 'radialBar',
                height: chartHeight,
                parentHeightOffset: 0,
                sparkline: { enabled: true },
            },
            series: [Number(config.satisfiedPercent || 0)],
            colors: [config.satisfiedColor || '#fb7185'],
            plotOptions: {
                radialBar: {
                    startAngle: -110,
                    endAngle: 110,
                    hollow: { size: compact ? '48%' : '62%' },
                    track: {
                        background: config.trackColor || '#e5e7eb',
                        strokeWidth: '100%',
                    },
                    dataLabels: {
                        show: true,
                        name: { show: false },
                        value: {
                            show: true,
                            offsetY: compact ? 3 : 6,
                            color: valueColor,
                            fontSize: chartHeight >= 180 ? '28px' : chartHeight >= 120 ? '22px' : chartHeight >= 90 ? '19px' : '17px',
                            fontWeight: 700,
                            formatter: () => config.satisfiedValue || '',
                        },
                    },
                },
            },
            stroke: { lineCap: 'round' },
        });

        const renderCardCharts = async (card) => {
            const config = parseConfig(card);
            if (!config) {
                return;
            }

            const gauge = card.querySelector('[data-dashboard-chart="gauge"]');
            const satisfied = card.querySelector('[data-dashboard-chart="satisfied"]');
            const breakdown = card.querySelector('[data-dashboard-chart="breakdown"]');

            if (!gauge || !satisfied || !breakdown) {
                return;
            }

            const chartSizes = resolveMainSummaryChartSizes(card);
            const signature = JSON.stringify({ config, chartSizes });
            if (card._reputalisChartsRenderingSignature === signature) {
                return;
            }

            if (
                card._reputalisChartsSignature === signature &&
                hasRenderedChart(gauge) &&
                hasRenderedChart(satisfied) &&
                hasRenderedChart(breakdown)
            ) {
                return;
            }

            if (! isVisibleForRender(card) && ! isVisibleForRender(gauge)) {
                queueDashboardChartsRetry();
                return;
            }

            card._reputalisChartsRenderingSignature = signature;

            try {
                const ApexCharts = await loadApexCharts();

                await new Promise((resolve) => window.requestAnimationFrame(resolve));

                if (! isVisibleForRender(card) && ! isVisibleForRender(gauge)) {
                    queueDashboardChartsRetry();
                    return;
                }

                destroyChart(gauge);
                destroyChart(satisfied);
                destroyChart(breakdown);

                const breakdownData = (config.scoreLabels || [])
                    .map((label, index) => ({
                        score: Number(label),
                        percentage: Number((config.scorePercentages || [])[index] || 0),
                        count: Number((config.scoreCounts || [])[index] || 0),
                    }))
                    .sort((a, b) => a.score - b.score);
                const valueColor = document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151';
                const { radialSize } = resolveMainSummaryChartSizes(card);

                gauge._reputalisChart = new ApexCharts(
                    gauge,
                    buildGaugeRadialOptions(config, valueColor, radialSize),
                );

                satisfied._reputalisChart = new ApexCharts(
                    satisfied,
                    buildSatisfiedRadialOptions(config, valueColor, radialSize),
                );

                breakdown._reputalisChart = new ApexCharts(
                    breakdown,
                    buildBreakdownChartOptions(config, breakdownData, resolveMainSummaryChartSizes(card)),
                );

                await gauge._reputalisChart.render();
                await satisfied._reputalisChart.render();
                await breakdown._reputalisChart.render();

                card._reputalisChartsSignature = signature;
            } finally {
                card._reputalisChartsRenderingSignature = null;
            }
        };

        const formatHourAxisLabels = (chartRoot, hourOffset = 0) => {
            if (!chartRoot) {
                return;
            }

            const axisGroup = chartRoot.querySelector('.apexcharts-xaxis-texts-g');

            if (!axisGroup) {
                return;
            }

            axisGroup.querySelectorAll('text').forEach((textNode, index) => {
                const hour = hourOffset + index;
                const x = textNode.getAttribute('x');
                const isBottomRow = hour % 2 === 1;

                textNode.textContent = '';

                const labelLine = document.createElementNS('http://www.w3.org/2000/svg', 'tspan');
                labelLine.setAttribute('x', x);
                labelLine.setAttribute('dy', isBottomRow ? '1.35em' : '0');
                labelLine.textContent = formatHourLabel(hour);

                textNode.appendChild(labelLine);
            });
        };

        const renderHistoryChart = async (card) => {
            const config = parseJsonConfig(card, '[data-dashboard-history-config]');
            if (!config) {
                return;
            }

            syncDashboardRangeKey(config);

            const chartElement = card.querySelector('[data-dashboard-chart="survey-history"]');
            if (!chartElement) {
                return;
            }

            const grouping = config.grouping || 'range';
            const isHoursGrouping = grouping === 'hours';
            const useHourShift = isHoursGrouping && isMobileHourlyViewport();
            const hourShift = useHourShift ? resolveHourShift() : null;
            const signature = JSON.stringify({
                config,
                hourShift,
                mobile: isMobileHourlyViewport(),
            });
            if (card._reputalisHistoryChartRenderingSignature === signature) {
                return;
            }

            if (
                card._reputalisHistoryChartSignature === signature &&
                hasRenderedChart(chartElement)
            ) {
                return;
            }

            if (!isVisibleForRender(card)) {
                queueDashboardChartsRetry();
                return;
            }

            card._reputalisHistoryChartRenderingSignature = signature;

            try {
                const ApexCharts = await loadApexCharts();

                await new Promise((resolve) => window.requestAnimationFrame(resolve));

                if (!isVisibleForRender(card)) {
                    queueDashboardChartsRetry();
                    return;
                }

                destroyChart(chartElement);

            let labels = config.labels || [];
            let counts = (config.counts || []).map((count) => Number(count || 0));
            let hourOffset = 0;

            if (useHourShift) {
                const sliced = sliceHourlySeries(labels, counts, hourShift);
                labels = sliced.labels;
                counts = sliced.series;
                hourOffset = sliced.hourOffset;
                syncHourShiftControls(card, hourShift);
            }

            const maxCount = Math.max(...counts, 0);
            const valueColor = document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#334155';
            const gridColor = document.documentElement.classList.contains('dark')
                ? 'rgba(148, 163, 184, 0.18)'
                : 'rgba(100, 116, 139, 0.24)';
            const xLabelRotate = isHoursGrouping ? 0 : (labels.length > 12 ? -35 : 0);
            const chartHeight = isHoursGrouping ? (useHourShift ? 248 : 272) : 232;
            const xAxisRightPadding = isHoursGrouping ? 14 : 36;

                chartElement._reputalisChart = new ApexCharts(chartElement, {
                chart: {
                    type: 'area',
                    height: chartHeight,
                    parentHeightOffset: 0,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: { enabled: false },
                    events: isHoursGrouping ? {
                        mounted: (chartContext) => formatHourAxisLabels(chartContext.el, hourOffset),
                        updated: (chartContext) => formatHourAxisLabels(chartContext.el, hourOffset),
                    } : {},
                },
                series: [{
                    name: config.seriesLabel || 'Encuestas',
                    data: counts,
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
                    size: grouping === 'range' && counts.length > 36 ? 0 : 4,
                    strokeWidth: 0,
                    colors: ['#76a99c'],
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: labels,
                    labels: {
                        rotate: xLabelRotate,
                        hideOverlappingLabels: ! isHoursGrouping,
                        trim: ! isHoursGrouping,
                        minHeight: isHoursGrouping ? 42 : undefined,
                        offsetY: isHoursGrouping ? 2 : 0,
                        style: {
                            colors: valueColor,
                            fontSize: isHoursGrouping ? '10px' : '11px',
                            fontWeight: 500,
                        },
                        formatter: isHoursGrouping
                            ? (_value, _timestamp, opts) => formatHourLabel(hourOffset + (opts?.i ?? 0))
                            : undefined,
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
                        text: config.seriesLabel || 'Encuestas',
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
                    padding: {
                        top: 6,
                        right: xAxisRightPadding,
                        bottom: isHoursGrouping ? 30 : 4,
                        left: 8,
                    },
                },
                tooltip: {
                    marker: { show: false },
                    y: {
                        formatter: (value) => `${value} ${config.tooltipLabel || config.seriesLabel || ''}`.trim(),
                    },
                },
                noData: {
                    text: config.emptyLabel || '',
                    align: 'center',
                    verticalAlign: 'middle',
                    style: {
                        color: valueColor,
                        fontSize: '13px',
                    },
                },
                });

                await chartElement._reputalisChart.render();

                if (isHoursGrouping) {
                    formatHourAxisLabels(chartElement, hourOffset);
                }

                card._reputalisHistoryChartSignature = signature;
            } finally {
                card._reputalisHistoryChartRenderingSignature = null;
            }
        };

        const renderTrendChart = async (card) => {
            const config = parseJsonConfig(card, '[data-dashboard-trend-config]');
            if (!config) {
                return;
            }

            syncDashboardRangeKey(config);

            const chartElement = card.querySelector('[data-dashboard-chart="score-trend"]');
            if (!chartElement) {
                return;
            }

            const isHourlyTrend = config.granularity === 'hour';
            const useHourShift = isHourlyTrend && isMobileHourlyViewport();
            const hourShift = useHourShift ? resolveHourShift() : null;
            const signature = JSON.stringify({
                config,
                hourShift,
                mobile: isMobileHourlyViewport(),
            });
            if (card._reputalisTrendChartRenderingSignature === signature) {
                return;
            }

            if (
                card._reputalisTrendChartSignature === signature &&
                hasRenderedChart(chartElement)
            ) {
                return;
            }

            if (! isVisibleForRender(chartElement) && ! isVisibleForRender(card)) {
                queueDashboardChartsRetry();
                return;
            }

            card._reputalisTrendChartRenderingSignature = signature;

            try {
                const ApexCharts = await loadApexCharts();

                await new Promise((resolve) => window.requestAnimationFrame(resolve));

                if (!isVisibleForRender(chartElement) && !isVisibleForRender(card)) {
                    queueDashboardChartsRetry();
                    return;
                }

                destroyChart(chartElement);

                let labels = config.labels || [];
                let values = (config.values || []).map((value) => value === null ? null : Number(value || 0));
                let hourOffset = 0;

                if (useHourShift) {
                    const sliced = sliceHourlySeries(labels, values, hourShift);
                    labels = sliced.labels;
                    values = sliced.series;
                    hourOffset = sliced.hourOffset;
                    syncHourShiftControls(card, hourShift);
                }

                const xAxisRightPadding = isHourlyTrend ? 14 : 36;
                const chartHeight = isHourlyTrend ? (useHourShift ? 248 : 272) : 238;
                const valueColor = document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#334155';
                const gridColor = document.documentElement.classList.contains('dark')
                    ? 'rgba(148, 163, 184, 0.18)'
                    : 'rgba(100, 116, 139, 0.24)';

                chartElement._reputalisChart = new ApexCharts(chartElement, {
                    chart: {
                        type: 'line',
                        height: chartHeight,
                        parentHeightOffset: 0,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        animations: { enabled: false },
                        events: isHourlyTrend ? {
                            mounted: (chartContext) => formatHourAxisLabels(chartContext.el, hourOffset),
                            updated: (chartContext) => formatHourAxisLabels(chartContext.el, hourOffset),
                        } : {},
                    },
                    series: [{
                        name: config.seriesLabel || 'Promedio',
                        data: values,
                    }],
                    colors: ['#6ea1cb'],
                    stroke: {
                        curve: 'straight',
                        width: 3,
                    },
                    markers: {
                        size: values.length > 40 ? 0 : 5,
                        strokeWidth: 0,
                        colors: ['#6ea1cb'],
                    },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: labels,
                        labels: {
                            rotate: isHourlyTrend ? 0 : -35,
                            hideOverlappingLabels: ! isHourlyTrend,
                            trim: ! isHourlyTrend,
                            minHeight: isHourlyTrend ? 42 : undefined,
                            offsetY: isHourlyTrend ? 2 : 0,
                            style: {
                                colors: valueColor,
                                fontSize: isHourlyTrend ? '10px' : '11px',
                                fontWeight: 500,
                            },
                            formatter: isHourlyTrend
                                ? (_value, _timestamp, opts) => formatHourLabel(hourOffset + (opts?.i ?? 0))
                                : undefined,
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
                            text: config.seriesLabel || 'Promedio',
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
                        padding: {
                            top: 8,
                            right: xAxisRightPadding,
                            bottom: isHourlyTrend ? 30 : 0,
                            left: 8,
                        },
                    },
                    tooltip: {
                        marker: { show: false },
                        y: {
                            formatter: (value) => value === null || value === undefined
                                ? ''
                                : `${Number(value).toFixed(2)} / 5`,
                        },
                    },
                    noData: {
                        text: config.emptyLabel || '',
                        align: 'center',
                        verticalAlign: 'middle',
                        style: {
                            color: valueColor,
                            fontSize: '13px',
                        },
                    },
                });

                await chartElement._reputalisChart.render();

                if (isHourlyTrend) {
                    formatHourAxisLabels(chartElement, hourOffset);
                }

                card._reputalisTrendChartSignature = signature;
            } finally {
                card._reputalisTrendChartRenderingSignature = null;
            }
        };

        const renderEmployeeDetailTrendChart = async (modal) => {
            const config = parseJsonConfig(modal, '[data-dashboard-employee-trend-config]');
            if (!config) {
                return;
            }

            const chartElement = modal.querySelector('[data-dashboard-chart="employee-trend"]');
            if (!chartElement) {
                return;
            }

            const signature = JSON.stringify(config);
            if (modal._reputalisEmployeeTrendChartRenderingSignature === signature) {
                return;
            }

            if (
                modal._reputalisEmployeeTrendChartSignature === signature &&
                hasRenderedChart(chartElement)
            ) {
                return;
            }

            if (! isVisibleForRender(chartElement) && ! isVisibleForRender(modal)) {
                queueDashboardChartsRetry();
                return;
            }

            modal._reputalisEmployeeTrendChartRenderingSignature = signature;

            try {
                const ApexCharts = await loadApexCharts();

                await new Promise((resolve) => window.requestAnimationFrame(resolve));

                if (!isVisibleForRender(chartElement) && !isVisibleForRender(modal)) {
                    queueDashboardChartsRetry();
                    return;
                }

                destroyChart(chartElement);

                const labels = config.labels || [];
                const values = (config.values || []).map((value) => value === null ? null : Number(value || 0));
                const valueColor = document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#334155';
                const gridColor = document.documentElement.classList.contains('dark')
                    ? 'rgba(148, 163, 184, 0.18)'
                    : 'rgba(100, 116, 139, 0.24)';

                chartElement._reputalisChart = new ApexCharts(chartElement, {
                    chart: {
                        type: 'line',
                        height: 340,
                        parentHeightOffset: 0,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        animations: { enabled: false },
                    },
                    series: [{
                        name: config.seriesLabel || 'Promedio',
                        data: values,
                    }],
                    colors: ['#6ea1cb'],
                    stroke: {
                        curve: 'straight',
                        width: 3,
                    },
                    markers: {
                        size: values.length > 40 ? 0 : 5,
                        strokeWidth: 0,
                        colors: ['#6ea1cb'],
                    },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: labels,
                        labels: {
                            rotate: -35,
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
                            text: config.seriesLabel || 'Promedio',
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
                        padding: {
                            top: 8,
                            right: 14,
                            bottom: 32,
                            left: 8,
                        },
                    },
                    tooltip: {
                        marker: { show: false },
                        y: {
                            formatter: (value) => value === null || value === undefined
                                ? ''
                                : `${Number(value).toFixed(2)} / 5`,
                        },
                    },
                    noData: {
                        text: config.emptyLabel || '',
                        align: 'center',
                        verticalAlign: 'middle',
                        style: {
                            color: valueColor,
                            fontSize: '13px',
                        },
                    },
                });

                await chartElement._reputalisChart.render();
                modal._reputalisEmployeeTrendChartSignature = signature;
            } finally {
                modal._reputalisEmployeeTrendChartRenderingSignature = null;
            }
        };

        const renderEmployeeDetailSatisfiedChart = async (modal) => {
            const config = parseJsonConfig(modal, '[data-dashboard-employee-satisfied-config]');
            if (!config) {
                return;
            }

            const chartElement = modal.querySelector('[data-dashboard-chart="employee-satisfied"]');
            if (!chartElement) {
                return;
            }

            const signature = JSON.stringify(config);
            if (modal._reputalisEmployeeSatisfiedChartRenderingSignature === signature) {
                return;
            }

            if (
                modal._reputalisEmployeeSatisfiedChartSignature === signature &&
                hasRenderedChart(chartElement)
            ) {
                return;
            }

            if (! isVisibleForRender(chartElement) && ! isVisibleForRender(modal)) {
                queueDashboardChartsRetry();
                return;
            }

            modal._reputalisEmployeeSatisfiedChartRenderingSignature = signature;

            try {
                const ApexCharts = await loadApexCharts();

                await new Promise((resolve) => window.requestAnimationFrame(resolve));

                if (! isVisibleForRender(chartElement) && ! isVisibleForRender(modal)) {
                    queueDashboardChartsRetry();
                    return;
                }

                destroyChart(chartElement);

                const valueColor = document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151';

                chartElement._reputalisChart = new ApexCharts(
                    chartElement,
                    buildSatisfiedRadialOptions(config, valueColor, 152, { compact: false }),
                );

                await chartElement._reputalisChart.render();
                modal._reputalisEmployeeSatisfiedChartSignature = signature;
            } finally {
                modal._reputalisEmployeeSatisfiedChartRenderingSignature = null;
            }
        };

        const renderImprovementDetailChart = async (modal) => {
            const config = parseJsonConfig(modal, '[data-dashboard-improvement-detail-config]');
            if (!config) {
                return;
            }

            const chartElement = modal.querySelector('[data-dashboard-chart="improvement-detail"]');
            if (!chartElement) {
                return;
            }

            const signature = JSON.stringify(config);
            if (modal._reputalisImprovementDetailChartRenderingSignature === signature) {
                return;
            }

            if (
                modal._reputalisImprovementDetailChartSignature === signature &&
                hasRenderedChart(chartElement)
            ) {
                return;
            }

            if (! isVisibleForRender(chartElement) && ! isVisibleForRender(modal)) {
                queueDashboardChartsRetry();
                return;
            }

            modal._reputalisImprovementDetailChartRenderingSignature = signature;

            try {
                const ApexCharts = await loadApexCharts();

                await new Promise((resolve) => window.requestAnimationFrame(resolve));

                if (! isVisibleForRender(chartElement) && ! isVisibleForRender(modal)) {
                    queueDashboardChartsRetry();
                    return;
                }

                destroyChart(chartElement);

                const labels = config.labels || [];
                const values = (config.values || []).map((value) => value === null ? null : Number(value || 0));
                const valueColor = document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#334155';
                const gridColor = document.documentElement.classList.contains('dark')
                    ? 'rgba(148, 163, 184, 0.18)'
                    : 'rgba(100, 116, 139, 0.24)';

                chartElement._reputalisChart = new ApexCharts(chartElement, {
                    chart: {
                        type: 'line',
                        height: 280,
                        parentHeightOffset: 0,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        animations: { enabled: false },
                    },
                    series: [{
                        name: config.seriesLabel || '%',
                        data: values,
                    }],
                    colors: ['#78a69e'],
                    stroke: {
                        curve: 'smooth',
                        width: 2,
                    },
                    markers: {
                        size: values.length > 40 ? 0 : 5,
                        strokeWidth: 0,
                        colors: ['#78a69e'],
                    },
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: labels,
                        labels: {
                            rotate: -35,
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
                        max: 100,
                        tickAmount: 5,
                        decimalsInFloat: 0,
                        title: {
                            text: config.yAxisLabel || '%',
                            style: {
                                color: valueColor,
                                fontSize: '11px',
                                fontWeight: 600,
                            },
                        },
                        labels: {
                            formatter: (value) => `${Number(value || 0).toFixed(0)}`,
                            style: {
                                colors: valueColor,
                                fontSize: '11px',
                            },
                        },
                    },
                    grid: {
                        borderColor: gridColor,
                        strokeDashArray: 0,
                        padding: {
                            top: 8,
                            right: 18,
                            bottom: 32,
                            left: 8,
                        },
                    },
                    tooltip: {
                        marker: { show: false },
                        y: {
                            formatter: (value) => {
                                if (value === null || value === undefined) {
                                    return '';
                                }

                                return `${Number(value).toFixed(1)}%`;
                            },
                        },
                    },
                    noData: {
                        text: config.emptyLabel || '',
                        align: 'center',
                        verticalAlign: 'middle',
                        style: {
                            color: valueColor,
                            fontSize: '13px',
                        },
                    },
                });

                await chartElement._reputalisChart.render();
                modal._reputalisImprovementDetailChartSignature = signature;
            } finally {
                modal._reputalisImprovementDetailChartRenderingSignature = null;
            }
        };

        let scheduled = false;

        const bindMainSummaryResizeObservers = () => {
            document.querySelectorAll('[data-dashboard-summary-chart]').forEach((card) => {
                if (card._reputalisSummaryResizeObserver) {
                    return;
                }

                card._reputalisSummaryResizeObserver = new ResizeObserver(() => {
                    card._reputalisChartsSignature = null;
                    card._reputalisChartsRenderingSignature = null;
                    queueDashboardChartsRetry(80);
                });
                card._reputalisSummaryResizeObserver.observe(card);
            });
        };

        window.reputalisInitClientDashboardCharts = () => {
            if (scheduled) {
                return;
            }

            scheduled = true;

            window.requestAnimationFrame(() => {
                scheduled = false;
                resetStaleDashboardChartSignatures();
                bindMainSummaryResizeObservers();
                bindHourShiftControls();

                document
                    .querySelectorAll('[data-dashboard-summary-chart]')
                    .forEach((card) => {
                        renderCardCharts(card).catch((error) => {
                            console.error('Unable to render dashboard charts', error);
                        });
                    });

                document
                    .querySelectorAll('[data-dashboard-history-chart]')
                    .forEach((card) => {
                        renderHistoryChart(card).catch((error) => {
                            console.error('Unable to render dashboard history chart', error);
                        });
                    });

                document
                    .querySelectorAll('[data-dashboard-trend-chart]')
                    .forEach((card) => {
                        renderTrendChart(card).catch((error) => {
                            console.error('Unable to render dashboard trend chart', error);
                        });
                    });

                document
                    .querySelectorAll('[data-dashboard-employee-detail]')
                    .forEach((modal) => {
                        renderEmployeeDetailTrendChart(modal).catch((error) => {
                            console.error('Unable to render employee detail trend chart', error);
                        });

                        renderEmployeeDetailSatisfiedChart(modal).catch((error) => {
                            console.error('Unable to render employee detail satisfied chart', error);
                        });
                    });

                document
                    .querySelectorAll('[data-dashboard-improvement-detail]')
                    .forEach((modal) => {
                        renderImprovementDetailChart(modal).catch((error) => {
                            console.error('Unable to render improvement detail chart', error);
                        });
                    });
            });
        };

        document.addEventListener('DOMContentLoaded', window.reputalisInitClientDashboardCharts);
        document.addEventListener('livewire:navigated', window.reputalisInitClientDashboardCharts);
        window.addEventListener('load', () => queueDashboardChartsRetry(60));
        window.addEventListener('resize', () => queueDashboardChartsRetry(120));

        document.addEventListener('livewire:init', () => {
            window.Livewire?.hook?.('morphed', window.reputalisInitClientDashboardCharts);
        });

        window.reputalisInitClientDashboardCharts();
        [80, 250, 600].forEach((delay) => {
            window.setTimeout(() => window.reputalisInitClientDashboardCharts?.(), delay);
        });
    })();
</script>
