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
                            fontSize: chartHeight >= 180 ? '28px' : chartHeight >= 90 ? '19px' : '17px',
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

            const signature = JSON.stringify(config);
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

                gauge._reputalisChart = new ApexCharts(gauge, {
                    chart: {
                        type: 'radialBar',
                        height: 180,
                        parentHeightOffset: 0,
                        sparkline: { enabled: true },
                    },
                    series: [Number(config.gaugePercent || 0)],
                    colors: [config.gaugeColor || '#9ca3af'],
                    plotOptions: {
                        radialBar: {
                            hollow: { size: '58%' },
                            track: {
                                background: config.trackColor || '#e5e7eb',
                                strokeWidth: '100%',
                            },
                            dataLabels: {
                                show: true,
                                name: {
                                    show: true,
                                    offsetY: 24,
                                    color: config.labelColor || '#6b7280',
                                    fontSize: '10px',
                                    fontWeight: 500,
                                },
                                value: {
                                    show: true,
                                    offsetY: -8,
                                    color: valueColor,
                                    fontSize: '28px',
                                    fontWeight: 700,
                                    formatter: () => config.gaugeValue || '',
                                },
                            },
                        },
                    },
                    labels: [config.gaugeLabel || ''],
                    stroke: { lineCap: 'round' },
                });

                satisfied._reputalisChart = new ApexCharts(
                    satisfied,
                    buildSatisfiedRadialOptions(config, valueColor),
                );

                breakdown._reputalisChart = new ApexCharts(breakdown, {
                    chart: {
                        type: 'bar',
                        height: 190,
                        width: '100%',
                        parentHeightOffset: 0,
                        toolbar: { show: false },
                        animations: { enabled: false },
                    },
                    series: [{
                        name: config.percentageLabel || '%',
                        data: breakdownData.map((item) => item.percentage),
                    }],
                    colors: ['#FF3901', '#FF9880', '#FFC60F', '#8DFFA8', '#01FF01'],
                    legend: { show: false },
                    plotOptions: {
                        bar: {
                            distributed: true,
                            horizontal: false,
                            borderRadius: 5,
                            columnWidth: '72%',
                        },
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    xaxis: {
                        categories: breakdownData.map((item) => `${item.percentage}%`),
                        labels: {
                            style: {
                                fontSize: '11px',
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
                            right: 0,
                            bottom: -6,
                            left: 0,
                        },
                        xaxis: { lines: { show: false } },
                        yaxis: { lines: { show: true } },
                    },
                    tooltip: {
                        y: {
                            formatter: (value, { dataPointIndex }) => {
                                const item = breakdownData[dataPointIndex] || { count: 0 };
                                return `${value}% (${item.count})`;
                            },
                        },
                    },
                });

                await gauge._reputalisChart.render();
                await satisfied._reputalisChart.render();
                await breakdown._reputalisChart.render();

                card._reputalisChartsSignature = signature;
            } finally {
                card._reputalisChartsRenderingSignature = null;
            }
        };

        const formatHourAxisLabels = (chartRoot) => {
            if (!chartRoot) {
                return;
            }

            const axisGroup = chartRoot.querySelector('.apexcharts-xaxis-texts-g');

            if (!axisGroup) {
                return;
            }

            axisGroup.querySelectorAll('text').forEach((textNode, index) => {
                const hour = index;
                const x = textNode.getAttribute('x');
                const isBottomRow = hour % 2 === 1;

                textNode.textContent = '';

                const labelLine = document.createElementNS('http://www.w3.org/2000/svg', 'tspan');
                labelLine.setAttribute('x', x);
                labelLine.setAttribute('dy', isBottomRow ? '1.35em' : '0');
                labelLine.textContent = `${hour}:00`;

                textNode.appendChild(labelLine);
            });
        };

        const renderHistoryChart = async (card) => {
            const config = parseJsonConfig(card, '[data-dashboard-history-config]');
            if (!config) {
                return;
            }

            const chartElement = card.querySelector('[data-dashboard-chart="survey-history"]');
            if (!chartElement) {
                return;
            }

            const signature = JSON.stringify(config);
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

            const labels = config.labels || [];
            const counts = (config.counts || []).map((count) => Number(count || 0));
            const grouping = config.grouping || 'range';
            const maxCount = Math.max(...counts, 0);
            const valueColor = document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#334155';
            const gridColor = document.documentElement.classList.contains('dark')
                ? 'rgba(148, 163, 184, 0.18)'
                : 'rgba(100, 116, 139, 0.24)';
            const xLabelRotate = grouping === 'hours' ? 0 : (labels.length > 12 ? -35 : 0);
            const chartHeight = grouping === 'hours' ? 272 : 232;
            const isHoursGrouping = grouping === 'hours';

                chartElement._reputalisChart = new ApexCharts(chartElement, {
                chart: {
                    type: 'area',
                    height: chartHeight,
                    parentHeightOffset: 0,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: { enabled: false },
                    events: isHoursGrouping ? {
                        mounted: (chartContext) => formatHourAxisLabels(chartContext.el),
                        updated: (chartContext) => formatHourAxisLabels(chartContext.el),
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
                            ? (_value, _timestamp, opts) => `${opts?.i ?? _value}:00`
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
                        right: 14,
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
                    formatHourAxisLabels(chartElement);
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

            const chartElement = card.querySelector('[data-dashboard-chart="score-trend"]');
            if (!chartElement) {
                return;
            }

            const signature = JSON.stringify(config);
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

                const labels = config.labels || [];
                const values = (config.values || []).map((value) => value === null ? null : Number(value || 0));
                const valueColor = document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#334155';
                const gridColor = document.documentElement.classList.contains('dark')
                    ? 'rgba(148, 163, 184, 0.18)'
                    : 'rgba(100, 116, 139, 0.24)';

                chartElement._reputalisChart = new ApexCharts(chartElement, {
                    chart: {
                        type: 'line',
                        height: 238,
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
                            bottom: 0,
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
                        height: 248,
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
                    buildSatisfiedRadialOptions(config, valueColor, 84, { compact: true }),
                );

                await chartElement._reputalisChart.render();
                modal._reputalisEmployeeSatisfiedChartSignature = signature;
            } finally {
                modal._reputalisEmployeeSatisfiedChartRenderingSignature = null;
            }
        };

        let scheduled = false;

        window.reputalisInitClientDashboardCharts = () => {
            if (scheduled) {
                return;
            }

            scheduled = true;

            window.requestAnimationFrame(() => {
                scheduled = false;
                resetStaleDashboardChartSignatures();

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
