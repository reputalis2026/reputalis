<x-filament-panels::page>
    @php
        /** @var \App\Models\Client $record */
        $record = $this->getRecord();
        $rangeContextSummary = $this->getRangeContextSummary();
        $mainReputationSummary = $this->getMainReputationSummary();
        $dateRange = $this->getInternalReputationDateRange();
        $improvementRanking = $this->getImprovementRanking();
        $employeeRanking = $this->getEmployeeRanking();
        $improvementDetail = $this->getSelectedImprovementDetail();
        $surveyHistory = $this->getSurveyHistory();
        $forceSurveyHistoryHours = $this->shouldForceSurveyHistoryHours();
        $scoreTrend = $this->getScoreTrend();
        $scoreColors = [
            1 => '#FF3901',
            2 => '#FF9880',
            3 => '#FFC60F',
            4 => '#8DFFA8',
            5 => '#01FF01',
        ];
        $mainSummaryChartConfig = [
            'gaugePercent' => $mainReputationSummary['gauge_percent'],
            'gaugeColor' => $mainReputationSummary['gauge_color'],
            'gaugeValue' => $mainReputationSummary['avg_score'],
            'gaugeLabel' => __('client.dashboard.main_summary.out_of_five'),
            'satisfiedPercent' => (float) ($mainReputationSummary['satisfied_pct_raw'] ?? 0),
            'satisfiedColor' => ((float) ($mainReputationSummary['satisfied_pct_raw'] ?? 0)) > 50 ? '#22c55e' : '#fb7185',
            'satisfiedValue' => $mainReputationSummary['satisfied_pct'],
            'trackColor' => '#e5e7eb',
            'scoreLabels' => collect($mainReputationSummary['score_breakdown'])
                ->pluck('score')
                ->map(fn ($score) => (string) $score)
                ->all(),
            'scorePercentages' => collect($mainReputationSummary['score_breakdown'])->pluck('percentage')->all(),
            'scoreCounts' => collect($mainReputationSummary['score_breakdown'])->pluck('count')->all(),
            'percentageLabel' => __('client.dashboard.main_summary.percentage_label'),
            'surveysTooltipLabel' => __('client.dashboard.main_summary.breakdown_surveys_tooltip'),
            'scoreColors' => collect($mainReputationSummary['score_breakdown'])
                ->sortBy('score')
                ->map(fn (array $row): string => $scoreColors[$row['score']] ?? '#9ca3af')
                ->values()
                ->all(),
            'labelColor' => '#6b7280',
        ];
        $surveyHistoryChartConfig = [
            'labels' => $surveyHistory['labels'],
            'counts' => $surveyHistory['counts'],
            'total' => $surveyHistory['total'],
            'grouping' => $surveyHistory['grouping'] ?? 'range',
            'seriesLabel' => __('client.dashboard.survey_history.y_axis_label'),
            'tooltipLabel' => __('client.dashboard.survey_history.series_label'),
            'emptyLabel' => __('client.dashboard.survey_history.empty'),
        ];
        $scoreTrendChartConfig = [
            'labels' => $scoreTrend['labels'],
            'values' => $scoreTrend['averages'],
            'granularity' => $scoreTrend['granularity'],
            'seriesLabel' => __('client.dashboard.score_trend.series_label'),
            'emptyLabel' => __('client.dashboard.score_trend.empty'),
        ];
    @endphp

    <div class="client-dashboard-page">
    <style>
        .client-dashboard-page {
            font-size: .875rem;
        }

        @media (min-width: 1024px) {
            .client-dashboard-row-1 {
                display: grid;
                grid-template-columns: minmax(16rem, 20rem) minmax(0, 1fr);
                gap: 1rem;
                align-items: stretch;
            }

            .client-dashboard-filter-card,
            .client-dashboard-filter-card > aside,
            .client-dashboard-main-summary-card {
                height: 100%;
            }

            .client-dashboard-filter-card > aside,
            .client-dashboard-main-summary-card {
                min-height: 19rem;
            }

            .client-dashboard-operations-grid {
                display: grid;
                grid-template-columns: repeat(12, minmax(0, 1fr));
                gap: 1rem;
            }

            .client-dashboard-main-summary-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: .75rem;
                min-width: 0;
            }

            .client-dashboard-card-employees,
            .client-dashboard-card-improvements {
                grid-column: span 6 / span 6;
            }

            .client-dashboard-card-employees {
                order: 1;
            }

            .client-dashboard-card-improvements {
                order: 2;
            }

            .client-dashboard-card-calls {
                grid-column: 1 / -1;
                order: 3;
            }
        }

        .client-dashboard-employee-ranking-scroll {
            --employee-row-height: 4.85rem;
            --employee-row-gap: .55rem;
            max-height: calc((var(--employee-row-height) * 4) + (var(--employee-row-gap) * 3));
            min-width: 0;
            overflow-x: hidden;
            overflow-y: auto;
            padding-left: 1.45rem;
            padding-right: .65rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .65) transparent;
        }

        .client-dashboard-employee-ranking-scroll::-webkit-scrollbar {
            width: .45rem;
        }

        .client-dashboard-employee-ranking-scroll::-webkit-scrollbar-thumb {
            border-radius: 9999px;
            background: rgba(148, 163, 184, .65);
        }

        .client-dashboard-employee-ranking-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .client-dashboard-insights-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        @media (min-width: 1280px) {
            .client-dashboard-insights-row {
                grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
            }
        }

        .client-dashboard-insights-row > .client-dashboard-card-improvements {
            grid-column: auto;
            order: initial;
        }

        .client-dashboard-main-summary-card {
            min-width: 0;
            container-type: inline-size;
            container-name: main-summary;
        }

        .client-dashboard-main-summary-grid {
            display: grid;
            grid-template-columns: minmax(0, .9fr) minmax(0, .85fr) minmax(0, 1fr);
            gap: .75rem;
            min-width: 0;
        }

        @container main-summary (max-width: 36rem) {
            .client-dashboard-main-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .client-dashboard-main-summary-metric--breakdown {
                grid-column: 1 / -1;
            }
        }

        @container main-summary (max-width: 22rem) {
            .client-dashboard-main-summary-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .client-dashboard-main-summary-metric--breakdown {
                grid-column: auto;
            }
        }

        .client-dashboard-main-summary-metric {
            min-width: 0;
            overflow: hidden;
            text-align: center;
            min-height: clamp(11rem, 32cqw, 15rem);
        }

        .client-dashboard-main-summary-chart-wrap {
            display: flex;
            min-height: clamp(6.5rem, 30cqw, 12rem);
            width: 100%;
            align-items: center;
            justify-content: center;
        }

        .client-dashboard-main-summary-chart-shell {
            width: min(11.25rem, 100%);
            max-width: 100%;
            aspect-ratio: 1;
        }

        .client-dashboard-main-summary-chart-shell [data-dashboard-chart] {
            width: 100%;
            height: 100%;
        }

        .client-dashboard-main-summary-breakdown {
            width: 100%;
            max-width: 100%;
            min-height: clamp(8.5rem, 22cqw, 12rem);
        }

        .client-dashboard-main-summary-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: #79ad99;
            color: #ffffff;
            padding: .75rem 1rem;
        }

        .client-dashboard-main-summary-header h3 {
            margin: 0;
            font-size: .84rem;
            font-weight: 700;
            line-height: 1.25rem;
        }

        .client-dashboard-main-summary-count {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, .18);
            padding: .3rem .55rem;
            font-size: .64rem;
            font-weight: 650;
            white-space: nowrap;
        }

        .client-dashboard-filter-header {
            background: #f39c12;
            color: #ffffff;
            padding: .75rem 1rem;
        }

        .client-dashboard-filter-header h3 {
            margin: 0;
            font-size: .84rem;
            font-weight: 700;
            line-height: 1.25rem;
        }

        .client-dashboard-filter-surveys-highlight {
            margin-top: .15rem;
            padding: 1.1rem 1rem 1.15rem;
            border-radius: .8rem;
            background: linear-gradient(145deg, #6fb842 0%, #85cc56 55%, #9ad86a 100%);
            box-shadow:
                0 10px 24px rgba(133, 204, 86, .32),
                inset 0 1px 0 rgba(255, 255, 255, .22);
            text-align: center;
        }

        .client-dashboard-filter-surveys-label {
            margin: 0;
            color: rgba(255, 255, 255, .94);
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .05em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .client-dashboard-filter-surveys-value {
            margin: .4rem 0 0;
            color: #ffffff;
            font-size: 2.45rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.03em;
            text-shadow: 0 1px 2px rgba(45, 90, 25, .2);
        }

        .client-dashboard-filter-dates {
            padding-top: .15rem;
        }

        .client-dashboard-filter-dates .client-dashboard-metric-label {
            font-size: .75rem;
            font-weight: 600;
        }

        .client-dashboard-filter-dates .client-dashboard-metric-value {
            font-size: .8rem;
            font-weight: 650;
        }

        .apexcharts-tooltip.reputalis-breakdown-tooltip {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }

        .apexcharts-tooltip.reputalis-breakdown-tooltip .apexcharts-tooltip-series-group {
            background: transparent !important;
            padding: 0 !important;
        }

        .client-dashboard-metric-label {
            color: #6b7280;
            font-size: .8125rem;
            font-weight: 600;
            line-height: 1.25rem;
        }

        .dark .client-dashboard-metric-label {
            color: #9ca3af;
        }

        .client-dashboard-metric-value {
            color: #374151;
            font-weight: 700;
        }

        .dark .client-dashboard-metric-value {
            color: #e5e7eb;
        }

        .client-dashboard-metric-value-lg {
            font-size: 1.65rem;
            line-height: 2.25rem;
            letter-spacing: -0.025em;
        }

        .client-dashboard-improvement-ranking-card {
            overflow: hidden;
            border-radius: .875rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
            border: 1px solid rgba(15, 23, 42, .08);
        }

        .dark .client-dashboard-improvement-ranking-card {
            background: rgb(17 24 39);
            border-color: rgba(255, 255, 255, .1);
        }

        .client-dashboard-improvement-ranking-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: #ef7f83;
            color: #ffffff;
            padding: .75rem 1rem;
        }

        .client-dashboard-improvement-ranking-header h3 {
            margin: 0;
            font-size: .84rem;
            font-weight: 700;
            line-height: 1.25rem;
        }

        .client-dashboard-improvement-ranking-order {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, .18);
            padding: .3rem .55rem;
            font-size: .64rem;
            font-weight: 650;
            white-space: nowrap;
        }

        .client-dashboard-improvement-ranking-body {
            padding: .9rem 1rem;
            background: #f8fafc;
        }

        .dark .client-dashboard-improvement-ranking-body {
            background: rgba(31, 41, 55, .62);
        }

        .client-dashboard-improvement-ranking-question {
            margin-bottom: .8rem;
            padding-bottom: .7rem;
            border-bottom: 1px solid rgba(148, 163, 184, .25);
            font-size: .73rem;
            color: #475569;
        }

        .dark .client-dashboard-improvement-ranking-question {
            color: #cbd5e1;
            border-color: rgba(255, 255, 255, .1);
        }

        .client-dashboard-improvement-ranking-scroll {
            max-height: 25rem;
            overflow-y: auto;
            padding-left: 1.25rem;
            padding-right: 1rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .65) transparent;
        }

        .client-dashboard-improvement-ranking-scroll::-webkit-scrollbar {
            width: .45rem;
        }

        .client-dashboard-improvement-ranking-scroll::-webkit-scrollbar-thumb {
            border-radius: 9999px;
            background: rgba(148, 163, 184, .65);
        }

        .client-dashboard-improvement-row {
            appearance: none;
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(11rem, .7fr);
            align-items: stretch;
            width: calc(100% - .35rem);
            margin-inline: auto;
            margin-bottom: .55rem;
            overflow: visible;
            border-radius: .45rem;
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, .08);
            box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
            color: inherit;
            cursor: pointer;
            font: inherit;
            text-align: left;
            transition: box-shadow .24s ease, transform .24s cubic-bezier(.22, 1, .36, 1), border-color .2s ease;
        }

        .client-dashboard-improvement-row:hover {
            border-color: rgba(239, 127, 131, .28);
            box-shadow: 0 4px 12px rgba(15, 23, 42, .1);
            transform: translateX(.42rem);
        }

        .client-dashboard-improvement-info-tab {
            position: absolute;
            top: 50%;
            left: 0;
            z-index: 3;
            display: flex;
            width: 1.15rem;
            height: calc(100% - .85rem);
            max-height: 3.15rem;
            min-height: 2.55rem;
            align-items: center;
            justify-content: center;
            border-radius: .3rem 0 0 .3rem;
            background: linear-gradient(180deg, #ef7f83, #e57379);
            color: #ffffff;
            box-shadow: -2px 0 8px rgba(239, 127, 131, .22);
            opacity: 0;
            pointer-events: none;
            transform: translate(calc(-100% - .35rem), -50%);
            transition: transform .28s cubic-bezier(.22, 1, .36, 1), opacity .2s ease;
        }

        .client-dashboard-improvement-info-tab-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: .58rem;
            font-weight: 700;
            letter-spacing: .04em;
            white-space: nowrap;
        }

        .client-dashboard-improvement-row:hover .client-dashboard-improvement-info-tab {
            opacity: 1;
            transform: translate(-100%, -50%);
        }

        .client-dashboard-improvement-row::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: .25rem;
            background: #ef7f83;
            transition: opacity .2s ease;
        }

        .client-dashboard-improvement-row:hover::before {
            opacity: 0;
        }

        .client-dashboard-improvement-row.is-inactive {
            border-style: dashed;
            background: #f1f5f9;
            opacity: .68;
            filter: grayscale(.35);
        }

        .client-dashboard-improvement-row.is-inactive:hover {
            opacity: .82;
        }

        .dark .client-dashboard-improvement-row:hover {
            border-color: rgba(239, 127, 131, .42);
            box-shadow: 0 4px 14px rgba(0, 0, 0, .22);
        }

        .dark .client-dashboard-improvement-row {
            background: rgb(17 24 39);
            border-color: rgba(255, 255, 255, .1);
            box-shadow: none;
        }

        .dark .client-dashboard-improvement-row.is-inactive {
            background: rgba(31, 41, 55, .72);
        }

        .client-dashboard-improvement-label {
            display: flex;
            min-height: 3.65rem;
            align-items: center;
            padding: .7rem .85rem .7rem 1.1rem;
            color: #475569;
            font-size: .73rem;
            font-weight: 500;
        }

        .client-dashboard-improvement-inactive-badge {
            display: inline-flex;
            width: fit-content;
            margin-left: .35rem;
            border-radius: 9999px;
            background: rgba(100, 116, 139, .12);
            padding: .12rem .42rem;
            color: #64748b;
            font-size: .56rem;
            font-weight: 800;
            line-height: .85rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            vertical-align: middle;
        }

        .dark .client-dashboard-improvement-inactive-badge {
            background: rgba(148, 163, 184, .16);
            color: #cbd5e1;
        }

        .dark .client-dashboard-improvement-label {
            color: #f8fafc;
        }

        .client-dashboard-improvement-negative {
            display: flex;
            min-height: 3.65rem;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-left: 1px solid rgba(15, 23, 42, .08);
            padding: .55rem .85rem;
            text-align: center;
        }

        .dark .client-dashboard-improvement-negative {
            border-color: rgba(255, 255, 255, .1);
        }

        .client-dashboard-improvement-negative-title {
            color: #475569;
            font-size: .64rem;
            font-weight: 800;
            line-height: .95rem;
        }

        .client-dashboard-improvement-negative-value {
            margin-top: .1rem;
            color: #475569;
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.2rem;
        }

        .client-dashboard-improvement-negative-count {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            color: #64748b;
            font-size: .64rem;
        }

        .client-dashboard-improvement-negative-count::before,
        .client-dashboard-improvement-footnote::before {
            content: "";
            width: .42rem;
            height: .42rem;
            flex: 0 0 .42rem;
            border-radius: 9999px;
            background: #f59e0b;
        }

        .client-dashboard-improvement-footnote {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-top: .75rem;
            color: #475569;
            font-size: .68rem;
        }

        @media (max-width: 700px) {
            .client-dashboard-improvement-row {
                grid-template-columns: minmax(0, 1fr);
            }

            .client-dashboard-improvement-negative {
                border-left: 0;
                border-top: 1px solid rgba(15, 23, 42, .08);
            }
        }

        .client-dashboard-survey-history-card {
            display: flex;
            min-height: 22rem;
            overflow: hidden;
            flex-direction: column;
            border-radius: .875rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
            border: 1px solid rgba(15, 23, 42, .08);
        }

        .client-dashboard-insights-row > .client-dashboard-survey-history-card:not(.client-dashboard-score-trend-card) {
            min-height: 0;
        }

        .client-dashboard-survey-history-card.is-hours-grouping {
            min-height: 24rem;
        }

        .client-dashboard-insights-row > .client-dashboard-survey-history-card.is-hours-grouping:not(.client-dashboard-score-trend-card) {
            min-height: 0;
        }

        .client-dashboard-score-trend-card {
            position: relative;
            z-index: 2;
            min-height: 20rem;
            overflow: hidden;
        }

        .client-dashboard-score-trend-card.is-detail-open {
            z-index: 20;
        }

        .client-dashboard-score-trend-detail-backdrop {
            position: absolute;
            inset: 0;
            z-index: 25;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            background: rgba(15, 23, 42, .18);
        }

        .client-dashboard-score-trend-detail-modal {
            position: relative;
            display: flex;
            width: min(100%, 26rem);
            max-height: min(22rem, calc(100% - 1.5rem));
            flex-direction: column;
            overflow: hidden;
            border-radius: .65rem;
            background: #ffffff;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .16);
        }

        .dark .client-dashboard-score-trend-detail-modal {
            background: rgb(17 24 39);
        }

        .client-dashboard-score-trend-detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            padding: .8rem .9rem .65rem;
        }

        .client-dashboard-score-trend-detail-title {
            margin: 0;
            color: #4b5563;
            font-size: .9rem;
            font-weight: 600;
            line-height: 1.3rem;
        }

        .dark .client-dashboard-score-trend-detail-title {
            color: #d1d5db;
        }

        .client-dashboard-score-trend-detail-close {
            display: inline-flex;
            width: 1.75rem;
            height: 1.75rem;
            flex: 0 0 1.75rem;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 9999px;
            background: #374151;
            color: #ffffff;
            cursor: pointer;
        }

        .client-dashboard-score-trend-detail-table-wrap {
            overflow: auto;
            max-height: 14.5rem;
            padding: 0 .9rem .85rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .65) transparent;
        }

        .client-dashboard-score-trend-detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .84rem;
        }

        .client-dashboard-score-trend-detail-table thead th {
            background: #f59e0b;
            color: #ffffff;
            padding: .5rem .7rem;
            font-size: .8rem;
            font-weight: 700;
            text-align: left;
        }

        .client-dashboard-score-trend-detail-table thead th:nth-child(2),
        .client-dashboard-score-trend-detail-table thead th:nth-child(3),
        .client-dashboard-score-trend-detail-table tbody td:nth-child(2),
        .client-dashboard-score-trend-detail-table tbody td:nth-child(3) {
            text-align: center;
        }

        .client-dashboard-score-trend-detail-table tbody td {
            border-top: 1px solid rgba(148, 163, 184, .28);
            color: #4b5563;
            padding: .55rem .7rem;
        }

        .dark .client-dashboard-score-trend-detail-table tbody td {
            border-color: rgba(255, 255, 255, .1);
            color: #e2e8f0;
        }

        .client-dashboard-score-trend-detail-empty {
            padding: .75rem .9rem 1rem;
            color: #6b7280;
            font-size: .84rem;
        }

        .client-dashboard-improvement-detail-backdrop {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, .42);
        }

        .client-dashboard-improvement-detail-modal,
        .client-dashboard-employee-detail-modal {
            display: flex;
            width: min(100%, 72rem);
            flex-direction: column;
            overflow: hidden;
            border-radius: .9rem;
            background: #ffffff;
            box-shadow: 0 18px 42px rgba(15, 23, 42, .22);
        }

        .client-dashboard-employee-detail-modal {
            height: min(94vh, 64rem);
            max-height: min(94vh, 64rem);
        }

        .client-dashboard-improvement-detail-modal {
            height: min(88vh, 54rem);
            max-height: min(88vh, 54rem);
        }

        .dark .client-dashboard-improvement-detail-modal,
        .dark .client-dashboard-employee-detail-modal {
            background: rgb(17 24 39);
        }

        .client-dashboard-improvement-detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .85rem;
            padding: 1.25rem 1.35rem 1rem;
        }

        .client-dashboard-improvement-detail-title {
            margin: 0;
            color: #4b5563;
            font-size: 1.2rem;
            font-weight: 600;
            line-height: 1.45rem;
        }

        .dark .client-dashboard-improvement-detail-title {
            color: #e5e7eb;
        }

        .client-dashboard-improvement-detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
            margin-top: .65rem;
            color: #64748b;
            font-size: .76rem;
            font-weight: 700;
        }

        .client-dashboard-improvement-detail-meta span {
            padding: 0 1.05rem;
            border-left: 1px solid rgba(148, 163, 184, .28);
        }

        .client-dashboard-improvement-detail-meta span:first-child {
            padding-left: 0;
            border-left: 0;
        }

        .client-dashboard-improvement-detail-close {
            display: inline-flex;
            width: 2rem;
            height: 2rem;
            flex: 0 0 2rem;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 9999px;
            background: #374151;
            color: #ffffff;
            cursor: pointer;
        }

        .client-dashboard-improvement-detail-body {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: .45rem;
            flex: 1;
            min-height: 0;
            padding: 0 1.35rem .85rem;
            border-top: 1px solid rgba(148, 163, 184, .18);
            overflow: hidden;
        }

        .client-dashboard-improvement-detail-chart-panel,
        .client-dashboard-improvement-detail-employees {
            display: flex;
            width: 100%;
            min-width: 0;
            flex-direction: column;
        }

        .client-dashboard-improvement-detail-chart-panel {
            flex: 0 0 auto;
            padding: .5rem 0 0;
        }

        .client-dashboard-improvement-detail-chart-title {
            margin: .35rem 0 .1rem;
            color: #64748b;
            font-size: .82rem;
            font-weight: 700;
            line-height: 1.2rem;
            text-align: center;
        }

        .client-dashboard-improvement-detail-note {
            margin: 0 0 .35rem;
            color: #64748b;
            font-size: .72rem;
            line-height: 1rem;
            text-align: center;
        }

        .client-dashboard-improvement-detail-chart {
            height: 17.5rem;
            min-height: 17.5rem;
            flex: 0 0 auto;
        }

        .client-dashboard-improvement-detail-employees {
            flex: 0 0 auto;
            align-self: center;
            width: min(100%, 50rem);
            margin-inline: auto;
            border-radius: .7rem;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, .22);
            padding: .85rem 1.1rem .95rem;
        }

        .dark .client-dashboard-improvement-detail-employees {
            background: rgba(31, 41, 55, .62);
            border-color: rgba(255, 255, 255, .1);
        }

        .client-dashboard-improvement-detail-employees-title {
            margin: 0 0 .65rem;
            color: #475569;
            font-size: .84rem;
            font-weight: 800;
            line-height: 1.2rem;
            text-align: center;
        }

        .dark .client-dashboard-improvement-detail-employees-title {
            color: #e2e8f0;
        }

        .client-dashboard-improvement-detail-employee-list {
            display: flex;
            flex-direction: column;
            gap: .7rem;
            overflow-y: auto;
            padding-right: .15rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .65) transparent;
        }

        .client-dashboard-improvement-detail-employee {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) minmax(3.5rem, auto);
            align-items: center;
            gap: .85rem;
            min-height: 3.85rem;
            border-radius: .65rem;
            background: #ffffff;
            padding: .85rem 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
        }

        .client-dashboard-improvement-detail-employee.is-inactive {
            border-style: dashed;
            background: #f1f5f9;
            opacity: .68;
            filter: grayscale(.35);
        }

        .dark .client-dashboard-improvement-detail-employee {
            background: rgb(17 24 39);
        }

        .dark .client-dashboard-improvement-detail-employee.is-inactive {
            background: rgba(31, 41, 55, .72);
        }

        .client-dashboard-improvement-detail-employee-avatar {
            display: inline-flex;
            width: 2.85rem;
            height: 2.85rem;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 9999px;
            background: #e2e8f0;
            color: #475569;
            font-size: .8rem;
            font-weight: 800;
        }

        .client-dashboard-improvement-detail-employee-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .client-dashboard-improvement-detail-employee-main {
            display: flex;
            min-width: 0;
            width: 100%;
            flex-direction: column;
            justify-content: center;
        }

        .client-dashboard-improvement-detail-employee-name {
            margin: 0;
            overflow: hidden;
            color: #334155;
            font-size: .88rem;
            font-weight: 700;
            line-height: 1.2rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .client-dashboard-improvement-detail-employee-name {
            color: #f8fafc;
        }

        .client-dashboard-improvement-detail-employee-bar {
            width: 100%;
            height: .4rem;
            margin-top: .5rem;
            overflow: hidden;
            border-radius: 9999px;
            background: #e2e8f0;
        }

        .client-dashboard-improvement-detail-employee-bar span {
            display: block;
            min-width: 0;
            height: 100%;
            border-radius: inherit;
            background: #FF9880;
        }

        .client-dashboard-improvement-detail-employee-count {
            color: #475569;
            font-size: .84rem;
            font-weight: 800;
            line-height: 1.1rem;
            min-width: 3.25rem;
            padding-left: .25rem;
            text-align: right;
            white-space: nowrap;
        }

        .client-dashboard-improvement-detail-employee-count small {
            display: block;
            margin-top: .12rem;
            color: #94a3b8;
            font-size: .72rem;
            font-weight: 700;
            line-height: 1rem;
        }

        .client-dashboard-improvement-detail-employee-empty {
            margin: 0;
            border-radius: .55rem;
            background: #ffffff;
            padding: .65rem .75rem;
            color: #64748b;
            font-size: .74rem;
            line-height: 1.05rem;
            text-align: center;
        }

        .dark .client-dashboard-improvement-detail-employee-empty {
            background: rgb(17 24 39);
            color: #cbd5e1;
        }

        .dark .client-dashboard-survey-history-card {
            background: rgb(17 24 39);
            border-color: rgba(255, 255, 255, .1);
        }

        .client-dashboard-survey-history-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid rgba(15, 23, 42, .08);
            padding: .85rem 1rem;
        }

        .dark .client-dashboard-survey-history-header {
            border-color: rgba(255, 255, 255, .1);
        }

        .client-dashboard-survey-history-title {
            display: flex;
            align-items: center;
            gap: .55rem;
            color: #475569;
            font-size: .84rem;
            font-weight: 700;
        }

        .dark .client-dashboard-survey-history-title {
            color: #f8fafc;
        }

        .client-dashboard-survey-history-icon {
            display: flex;
            width: 1.35rem;
            height: 1.35rem;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: #f59e0b;
            color: #ffffff;
        }

        .client-dashboard-survey-history-actions {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: .35rem;
            color: #64748b;
            font-size: .64rem;
            font-weight: 700;
        }

        .client-dashboard-survey-history-pill {
            border-radius: .25rem;
            border: 1px solid rgba(148, 163, 184, .65);
            padding: .2rem .45rem;
            background: #ffffff;
            color: #64748b;
            line-height: 1;
            cursor: pointer;
            font: inherit;
        }

        button.client-dashboard-survey-history-pill {
            appearance: none;
        }

        .client-dashboard-survey-history-pill.is-active {
            border-color: #f59e0b;
            background: #f59e0b;
            color: #ffffff;
        }

        .dark .client-dashboard-survey-history-pill {
            background: rgb(31 41 55);
            color: #cbd5e1;
        }

        .client-dashboard-survey-history-body {
            min-height: 17rem;
            flex: 1;
            padding: .65rem 1rem 1rem;
        }

        .client-dashboard-insights-row > .client-dashboard-survey-history-card:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-body {
            min-height: 0;
            flex: 0 0 auto;
            padding: .45rem .85rem .55rem;
        }

        .client-dashboard-survey-history-chart {
            min-height: 16rem;
            overflow: visible;
        }

        .client-dashboard-insights-row > .client-dashboard-survey-history-card:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-chart {
            min-height: 0;
            height: 14.5rem;
        }

        .client-dashboard-insights-row > .client-dashboard-survey-history-card.is-hours-grouping:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-chart {
            height: 17rem;
        }

        .client-dashboard-survey-history-card.is-hours-grouping .client-dashboard-survey-history-body {
            padding-bottom: 2.25rem;
        }

        .client-dashboard-insights-row > .client-dashboard-survey-history-card.is-hours-grouping:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-body {
            padding-bottom: 1.1rem;
        }

        .client-dashboard-score-trend-card .client-dashboard-survey-history-chart {
            min-height: 14.5rem;
        }

        @media (min-width: 1280px) {
            .client-dashboard-insights-row .client-dashboard-employee-row {
                grid-template-columns: minmax(0, 1.35fr) minmax(3rem, 0.9fr) minmax(3.25rem, 0.68fr) 3rem;
                gap: .3rem .35rem;
                min-height: 4.65rem;
                padding: .5rem .5rem .5rem .85rem;
            }

            .client-dashboard-insights-row .client-dashboard-employee-identity {
                justify-self: stretch;
                padding-right: 0;
            }

            .client-dashboard-insights-row .client-dashboard-employee-chart {
                justify-self: stretch;
                width: 100%;
                max-width: 100%;
            }

            .client-dashboard-insights-row .client-dashboard-employee-bar {
                max-width: .75rem;
                width: 100%;
            }

            .client-dashboard-insights-row .client-dashboard-employee-ranking-scroll {
                --employee-row-height: 4.65rem;
            }

            .client-dashboard-insights-row .client-dashboard-employee-avatar {
                width: 2.45rem;
                height: 2.45rem;
                flex-basis: 2.45rem;
            }

            .client-dashboard-insights-row .client-dashboard-employee-bars {
                min-height: 3rem;
                gap: .28rem;
                padding: 0 .1rem .1rem;
            }

            .client-dashboard-insights-row .client-dashboard-employee-score {
                width: 3rem;
                height: 3rem;
                flex-basis: 3rem;
                font-size: .8rem;
            }
        }

        .client-dashboard-employee-ranking-card {
            display: flex;
            min-width: 0;
            overflow: hidden;
            flex-direction: column;
            border-radius: .875rem;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
            border: 1px solid rgba(15, 23, 42, .08);
        }

        .dark .client-dashboard-employee-ranking-card {
            background: rgb(17 24 39);
            border-color: rgba(255, 255, 255, .1);
        }

        .client-dashboard-employee-ranking-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: linear-gradient(135deg, #7db5db, #93c5e3);
            color: #ffffff;
            padding: .75rem 1rem;
        }

        .client-dashboard-employee-ranking-header h3 {
            font-size: .84rem;
            font-weight: 700;
            line-height: 1.25rem;
            margin: 0;
        }

        .client-dashboard-employee-ranking-order {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, .18);
            padding: .3rem .55rem;
            font-size: .64rem;
            font-weight: 650;
            white-space: nowrap;
        }

        .client-dashboard-employee-ranking-body {
            display: flex;
            min-width: 0;
            min-height: 0;
            flex: 1;
            flex-direction: column;
            padding: .9rem .75rem;
            background: #f8fafc;
        }

        .dark .client-dashboard-employee-ranking-body {
            background: rgba(31, 41, 55, .62);
        }

        .client-dashboard-employee-row {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(3.25rem, 0.95fr) minmax(3.5rem, 0.72fr) 3.15rem;
            align-items: center;
            gap: .35rem .4rem;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            min-height: 4.85rem;
            margin-bottom: .5rem;
            overflow: visible;
            border-radius: .55rem;
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, .08);
            box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
            padding: .55rem .55rem .55rem .85rem;
            cursor: pointer;
            transition: box-shadow .24s ease, transform .24s cubic-bezier(.22, 1, .36, 1), border-color .2s ease;
        }

        .client-dashboard-employee-row:hover,
        .client-dashboard-employee-row:focus-visible {
            border-color: rgba(142, 197, 236, .45);
            box-shadow: 0 4px 12px rgba(15, 23, 42, .1);
            transform: translateX(.42rem);
            outline: none;
        }

        .client-dashboard-employee-row.is-inactive {
            border-style: dashed;
            background: #f1f5f9;
            opacity: .68;
            filter: grayscale(.35);
        }

        .client-dashboard-employee-row.is-inactive:hover,
        .client-dashboard-employee-row.is-inactive:focus-visible {
            opacity: .82;
        }

        .dark .client-dashboard-employee-row.is-inactive {
            background: rgba(31, 41, 55, .72);
        }

        .client-dashboard-employee-info-tab {
            position: absolute;
            top: 50%;
            left: 0;
            z-index: 3;
            display: flex;
            width: 1.15rem;
            height: calc(100% - .85rem);
            max-height: 3.15rem;
            min-height: 2.55rem;
            align-items: center;
            justify-content: center;
            border-radius: .3rem 0 0 .3rem;
            background: linear-gradient(180deg, #7db5db, #89b6d8);
            color: #ffffff;
            box-shadow: -2px 0 8px rgba(125, 181, 219, .24);
            opacity: 0;
            pointer-events: none;
            transform: translate(calc(-100% - .35rem), -50%);
            transition: transform .28s cubic-bezier(.22, 1, .36, 1), opacity .2s ease;
        }

        .client-dashboard-employee-info-tab-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-size: .58rem;
            font-weight: 700;
            letter-spacing: .04em;
            white-space: nowrap;
        }

        .client-dashboard-employee-row:hover .client-dashboard-employee-info-tab,
        .client-dashboard-employee-row:focus-visible .client-dashboard-employee-info-tab {
            opacity: 1;
            transform: translate(-100%, -50%);
        }

        .client-dashboard-employee-row:hover::before,
        .client-dashboard-employee-row:focus-visible::before {
            opacity: 0;
        }

        .client-dashboard-employee-row::before {
            content: "";
            position: absolute;
            inset: .45rem auto .45rem 0;
            width: .25rem;
            border-radius: 9999px;
            background: #8ec5ec;
            transition: opacity .2s ease;
        }

        .dark .client-dashboard-employee-row:hover,
        .dark .client-dashboard-employee-row:focus-visible {
            border-color: rgba(142, 197, 236, .5);
            box-shadow: 0 4px 14px rgba(0, 0, 0, .22);
        }

        .dark .client-dashboard-employee-row {
            background: rgb(17 24 39);
            border-color: rgba(255, 255, 255, .1);
            box-shadow: none;
        }

        .client-dashboard-employee-identity {
            display: flex;
            min-width: 0;
            flex: 1 1 auto;
            justify-self: stretch;
            align-items: center;
            gap: .55rem;
        }

        .client-dashboard-employee-avatar {
            display: flex;
            width: 2.55rem;
            height: 2.55rem;
            flex: 0 0 2.55rem;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: .35rem;
            background: #e0f2fe;
            color: #0369a1;
            font-size: .8rem;
            font-weight: 700;
        }

        .client-dashboard-employee-avatar img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .client-dashboard-employee-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .76rem;
            font-weight: 650;
            color: #475569;
        }

        .client-dashboard-employee-name-wrap {
            min-width: 0;
        }

        .client-dashboard-employee-inactive-badge {
            display: inline-flex;
            width: fit-content;
            margin-top: .18rem;
            border-radius: 9999px;
            background: rgba(100, 116, 139, .12);
            padding: .12rem .42rem;
            color: #64748b;
            font-size: .56rem;
            font-weight: 800;
            line-height: .85rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .dark .client-dashboard-employee-inactive-badge {
            background: rgba(148, 163, 184, .16);
            color: #cbd5e1;
        }

        .dark .client-dashboard-employee-name {
            color: #f8fafc;
        }

        .client-dashboard-employee-chart {
            min-width: 0;
            justify-self: stretch;
            width: 100%;
            max-width: 100%;
        }

        .client-dashboard-employee-bars {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            align-items: end;
            gap: .18rem;
            min-height: 3.2rem;
            border-bottom: 1px solid #9ca3af;
            padding: 0 .1rem .1rem;
        }

        .client-dashboard-employee-bar {
            min-height: 2px;
            max-width: .8rem;
            width: 100%;
            justify-self: center;
            border-radius: .08rem .08rem 0 0;
        }

        .client-dashboard-employee-bar-labels {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .18rem;
            margin-top: .18rem;
            color: #4b5563;
            font-size: .56rem;
            font-weight: 600;
            text-align: center;
        }

        .dark .client-dashboard-employee-bar-labels {
            color: #e5e7eb;
        }

        .client-dashboard-employee-surveys {
            box-sizing: border-box;
            min-width: 0;
            justify-self: stretch;
            border-left: 1px solid rgba(15, 23, 42, .08);
            border-right: 1px solid rgba(15, 23, 42, .08);
            padding: .2rem .35rem;
            text-align: center;
        }

        .dark .client-dashboard-employee-surveys {
            border-color: rgba(255, 255, 255, .1);
        }

        .client-dashboard-employee-surveys dt {
            color: #64748b;
            font-size: .58rem;
            font-weight: 700;
            line-height: 1.05;
        }

        .client-dashboard-employee-surveys dd {
            margin-top: .1rem;
            color: #64748b;
            font-size: 1.05rem;
            font-weight: 500;
            line-height: 1.35rem;
        }

        .client-dashboard-employee-score {
            display: flex;
            width: 3.15rem;
            height: 3.15rem;
            flex: 0 0 3.15rem;
            align-items: center;
            justify-content: center;
            justify-self: end;
            border: 3px solid #22c55e;
            border-radius: 9999px;
            color: #64748b;
            font-size: .82rem;
            font-weight: 800;
            line-height: 1;
            text-align: center;
        }

        .client-dashboard-employee-score small {
            font-size: .58rem;
            font-weight: 800;
        }

        .client-dashboard-employee-detail-backdrop {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, .42);
        }

        .client-dashboard-employee-detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .85rem;
            padding: 1.25rem 1.35rem 1rem;
        }

        .client-dashboard-employee-detail-title {
            margin: 0;
            color: #4b5563;
            font-size: 1.2rem;
            font-weight: 600;
            line-height: 1.45rem;
        }

        .dark .client-dashboard-employee-detail-title {
            color: #e5e7eb;
        }

        .client-dashboard-employee-detail-subtitle {
            margin: .4rem 0 0;
            color: #64748b;
            font-size: .96rem;
            line-height: 1.35rem;
        }

        .client-dashboard-employee-detail-subtitle strong {
            color: #475569;
            font-weight: 700;
        }

        .dark .client-dashboard-employee-detail-subtitle,
        .dark .client-dashboard-employee-detail-subtitle strong {
            color: #e2e8f0;
        }

        .client-dashboard-employee-detail-period {
            margin: .25rem 0 0;
            color: #94a3b8;
            font-size: .84rem;
            line-height: 1.25rem;
        }

        .client-dashboard-employee-detail-close {
            display: inline-flex;
            width: 2rem;
            height: 2rem;
            flex: 0 0 2rem;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 9999px;
            background: #374151;
            color: #ffffff;
            cursor: pointer;
        }

        .client-dashboard-employee-detail-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            align-items: start;
            gap: 1.15rem;
            padding: 0 1.35rem 1.25rem;
        }

        .client-dashboard-employee-detail-metric {
            display: flex;
            min-width: 0;
            flex-direction: column;
            align-items: center;
            gap: .45rem;
        }

        .client-dashboard-employee-detail-metric-label {
            display: flex;
            min-height: 2.5rem;
            margin: 0;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: .78rem;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
        }

        .client-dashboard-employee-detail-metric-value {
            display: flex;
            width: 100%;
            height: 7.75rem;
            align-items: center;
            justify-content: center;
        }

        .client-dashboard-employee-detail-metric-satisfied .client-dashboard-employee-detail-metric-value {
            overflow: visible;
        }

        .client-dashboard-employee-detail-score {
            display: flex;
            width: 7rem;
            height: 7rem;
            align-items: center;
            justify-content: center;
            border: 3px solid #22c55e;
            border-radius: 9999px;
            color: #64748b;
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1;
        }

        .client-dashboard-employee-detail-score small {
            font-size: .82rem;
            font-weight: 800;
        }

        .client-dashboard-employee-detail-surveys-value {
            color: #64748b;
            font-size: 1.85rem;
            font-weight: 500;
            line-height: 1;
            text-align: center;
        }

        .client-dashboard-employee-detail-satisfied-chart {
            width: 8.75rem;
            height: 8.75rem;
            flex: 0 0 8.75rem;
        }

        .dark .client-dashboard-employee-detail-surveys-value {
            color: #cbd5e1;
        }

        .client-dashboard-employee-detail-mini-chart {
            display: flex;
            width: 100%;
            max-width: 11rem;
            height: 100%;
            flex-direction: column;
            justify-content: flex-end;
        }

        .client-dashboard-employee-detail-mini-chart .client-dashboard-employee-bars {
            min-height: 4.75rem;
            gap: .32rem;
            padding: 0 .2rem .18rem;
        }

        .client-dashboard-employee-detail-mini-chart .client-dashboard-employee-bar {
            max-width: 1rem;
            width: 100%;
        }

        .client-dashboard-employee-detail-mini-chart .client-dashboard-employee-bar-labels {
            gap: .32rem;
            margin-top: .28rem;
            font-size: .64rem;
            font-weight: 700;
        }

        .client-dashboard-employee-detail-bottom {
            display: grid;
            min-height: 0;
            flex: 1;
            grid-template-columns: minmax(0, 1fr);
            border-top: 1px solid rgba(15, 23, 42, .08);
        }

        .client-dashboard-employee-detail-bottom.has-improvement {
            grid-template-columns: minmax(0, 1.5fr) minmax(16rem, 1fr);
            gap: 1.25rem;
        }

        .dark .client-dashboard-employee-detail-bottom {
            border-color: rgba(255, 255, 255, .1);
        }

        .client-dashboard-employee-detail-trend {
            display: flex;
            min-height: 0;
            min-width: 0;
            flex-direction: column;
            padding: .9rem 1.25rem 1.15rem;
        }

        .client-dashboard-employee-detail-bottom.has-improvement .client-dashboard-employee-detail-trend {
            padding-right: 0;
        }

        .client-dashboard-employee-detail-improvement {
            display: flex;
            min-height: 0;
            flex-direction: column;
            border-left: 1px solid rgba(15, 23, 42, .08);
            padding: .9rem 1.15rem 1.15rem .35rem;
        }

        .dark .client-dashboard-employee-detail-improvement {
            border-color: rgba(255, 255, 255, .1);
        }

        .client-dashboard-employee-detail-improvement-title {
            margin: 0 0 .65rem;
            color: #64748b;
            font-size: .8rem;
            font-weight: 700;
            text-align: center;
        }

        .client-dashboard-employee-detail-improvement-list {
            display: flex;
            min-height: 0;
            flex: 1;
            flex-direction: column;
            gap: .6rem;
            overflow-y: auto;
            padding-right: .15rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .65) transparent;
        }

        .client-dashboard-employee-detail-improvement-list::-webkit-scrollbar {
            width: .35rem;
        }

        .client-dashboard-employee-detail-improvement-list::-webkit-scrollbar-thumb {
            border-radius: 9999px;
            background: rgba(148, 163, 184, .65);
        }

        .client-dashboard-employee-detail-improvement-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: .55rem;
            border: 1px solid rgba(239, 68, 68, .14);
            border-radius: .65rem;
            background: linear-gradient(180deg, #ffffff 0%, #fff7f7 100%);
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            padding: .8rem .9rem;
        }

        .dark .client-dashboard-employee-detail-improvement-card {
            border-color: rgba(248, 113, 113, .2);
            background: linear-gradient(180deg, rgba(31, 41, 55, .78) 0%, rgba(69, 26, 26, .28) 100%);
        }

        .client-dashboard-employee-detail-improvement-card.is-inactive {
            border-style: dashed;
            background: #f1f5f9;
            opacity: .72;
            filter: grayscale(.35);
        }

        .dark .client-dashboard-employee-detail-improvement-card.is-inactive {
            background: rgba(31, 41, 55, .72);
        }

        .client-dashboard-employee-detail-improvement-label {
            margin: 0;
            color: #475569;
            font-size: .86rem;
            font-weight: 650;
            line-height: 1.3rem;
        }

        .dark .client-dashboard-employee-detail-improvement-label {
            color: #e2e8f0;
        }

        .client-dashboard-employee-detail-improvement-stats {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: .5rem;
        }

        .client-dashboard-employee-detail-improvement-percentage {
            color: #ef4444;
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .client-dashboard-employee-detail-improvement-count {
            color: #94a3b8;
            font-size: .74rem;
            font-weight: 600;
            line-height: 1.2;
            text-align: right;
        }

        .client-dashboard-employee-detail-improvement-bar {
            height: .48rem;
            overflow: hidden;
            border-radius: 9999px;
            background: rgba(239, 68, 68, .12);
        }

        .client-dashboard-employee-detail-improvement-bar span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #ff9880 0%, #ef4444 100%);
            min-width: .25rem;
        }

        .client-dashboard-employee-detail-trend-title {
            margin: 0 0 .45rem;
            color: #64748b;
            font-size: .8rem;
            font-weight: 700;
            text-align: center;
        }

        .client-dashboard-employee-detail-trend-chart {
            min-height: 22.5rem;
            flex: 1;
        }

        @media (max-width: 640px) {
            .client-dashboard-employee-detail-backdrop {
                align-items: flex-start;
                padding: .75rem;
                overflow-y: auto;
            }

            .client-dashboard-employee-detail-modal,
            .client-dashboard-improvement-detail-modal {
                width: 100%;
                height: auto;
                max-height: none;
                margin: 0 auto;
                overflow: visible;
            }

            .client-dashboard-employee-detail-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .client-dashboard-employee-detail-mini-chart {
                max-width: none;
            }

            .client-dashboard-employee-detail-bottom,
            .client-dashboard-employee-detail-bottom.has-improvement {
                display: flex;
                flex-direction: column;
                grid-template-columns: unset;
            }

            .client-dashboard-employee-detail-trend {
                flex: 0 0 auto;
                padding: .75rem 1rem 1.5rem;
            }

            .client-dashboard-employee-detail-bottom.has-improvement .client-dashboard-employee-detail-trend {
                padding-right: 1rem;
            }

            .client-dashboard-employee-detail-trend-chart {
                min-height: 13rem;
                overflow: hidden;
            }

            .client-dashboard-employee-detail-improvement {
                flex: 0 0 auto;
                border-top: 1px solid rgba(15, 23, 42, .08);
                border-left: 0;
                margin-top: .15rem;
                padding: 1rem 1rem 1.15rem;
                background: #ffffff;
            }

            .dark .client-dashboard-employee-detail-improvement {
                border-color: rgba(255, 255, 255, .1);
                background: rgb(17 24 39);
            }

            .client-dashboard-employee-detail-improvement-title {
                margin-bottom: .7rem;
            }

            .client-dashboard-employee-detail-improvement-list {
                max-height: none;
                overflow: visible;
            }

            .client-dashboard-improvement-detail-backdrop {
                align-items: flex-start;
                padding: .75rem;
                overflow-y: auto;
            }

            .client-dashboard-improvement-detail-body {
                overflow: visible;
            }

            .client-dashboard-improvement-detail-chart-panel {
                flex: 0 0 auto;
            }

            .client-dashboard-improvement-detail-chart {
                min-height: 13rem;
            }

            .client-dashboard-improvement-detail-employees {
                max-height: none;
            }

            .client-dashboard-improvement-detail-employee-list {
                max-height: none;
                overflow: visible;
            }
        }

        @media (max-width: 900px) {
            .client-dashboard-employee-ranking-scroll {
                --employee-row-height: 7.15rem;
            }

            .client-dashboard-employee-ranking-header {
                flex-direction: column;
                align-items: flex-start;
                gap: .45rem;
            }

            .client-dashboard-employee-row {
                grid-template-columns: minmax(0, 1fr) auto;
                grid-template-rows: auto auto;
                gap: .45rem .65rem;
                min-height: 0;
                padding: .65rem .75rem .65rem .95rem;
            }

            .client-dashboard-employee-identity {
                grid-column: 1;
                grid-row: 1;
                min-width: 0;
            }

            .client-dashboard-employee-name {
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .client-dashboard-employee-score {
                grid-column: 2;
                grid-row: 1;
                justify-self: end;
                align-self: center;
                width: 3.35rem;
                height: 3.35rem;
                font-size: .8rem;
            }

            .client-dashboard-employee-chart {
                grid-column: 1;
                grid-row: 2;
                justify-self: stretch;
                width: 100%;
                min-width: 0;
            }

            .client-dashboard-employee-bars {
                min-height: 2.85rem;
            }

            .client-dashboard-employee-bar {
                max-width: 1rem;
            }

            .client-dashboard-employee-surveys {
                grid-column: 2;
                grid-row: 2;
                justify-self: end;
                align-self: center;
                min-width: 4.5rem;
                border: 0;
                border-left: 1px solid rgba(15, 23, 42, .08);
                padding: .15rem 0 .15rem .65rem;
                text-align: center;
            }

            .dark .client-dashboard-employee-surveys {
                border-left-color: rgba(255, 255, 255, .1);
            }

            .client-dashboard-employee-surveys dd {
                font-size: 1.15rem;
                line-height: 1.35rem;
            }
        }

        @media (max-width: 1023.98px) {
            .client-dashboard-row-1,
            .client-dashboard-operations-grid,
            .client-dashboard-main-summary-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1rem;
            }
        }
    </style>

    <div class="space-y-6">
        @include('filament.components.client-dashboard.reputation-tabs', [
            'tabs' => $this->getReputationTabs(),
            'activeTab' => $activeReputationTab,
        ])

        @if ($activeReputationTab === 'internal')
            <section class="space-y-6" data-dashboard-section="internal-reputation">
                <div>
                    <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        {{ __('client.dashboard.internal.heading') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('client.dashboard.internal.description') }}
                    </p>
                </div>

                <div class="client-dashboard-row-1">
                    <div class="client-dashboard-filter-card">
                        @include('filament.components.client-dashboard.time-range-filter', [
                            'rangeTypes' => $this->getRangeTypeOptions(),
                            'activeRangeType' => $range_type,
                            'isCustomRange' => $dateRange->isCustom(),
                            'rangeContextSummary' => $rangeContextSummary,
                        ])
                    </div>

                    <section
                        class="client-dashboard-main-summary-card flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                        data-dashboard-section="main-reputation-summary"
                        data-dashboard-summary-chart
                        wire:key="main-summary-{{ $range_type }}-{{ $date_from ?? 'empty' }}-{{ $date_to ?? 'empty' }}"
                    >
                        <script type="application/json" data-dashboard-summary-config>
                            {!! json_encode($mainSummaryChartConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
                        </script>

                        <div class="client-dashboard-main-summary-header">
                            <h3>
                                {{ __('client.dashboard.main_summary.heading') }}
                            </h3>
                            <span class="client-dashboard-main-summary-count">
                                <x-filament::icon icon="heroicon-m-funnel" class="h-3.5 w-3.5" />
                                {{ trans_choice('client.dashboard.main_summary.surveys_count', $mainReputationSummary['total_surveys'], ['count' => $mainReputationSummary['total_surveys']]) }}
                            </span>
                        </div>

                        <div class="client-dashboard-main-summary-grid flex-1 p-4">
                            <div class="client-dashboard-main-summary-metric flex flex-col rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="client-dashboard-metric-label">
                                    {{ __('client.dashboard.main_summary.obtained_rating') }}
                                </p>
                                <div class="client-dashboard-main-summary-chart-wrap mt-2 flex-1">
                                    <div class="client-dashboard-main-summary-chart-shell">
                                        <div wire:ignore data-dashboard-chart="gauge"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="client-dashboard-main-summary-metric flex flex-col rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="client-dashboard-metric-label">
                                    {{ __('client.dashboard.main_summary.satisfied_customers') }}
                                </p>
                                <div class="client-dashboard-main-summary-chart-wrap mt-2 flex-1">
                                    <div class="client-dashboard-main-summary-chart-shell">
                                        <div wire:ignore data-dashboard-chart="satisfied"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="client-dashboard-main-summary-metric client-dashboard-main-summary-metric--breakdown flex flex-col rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="client-dashboard-metric-label">
                                    {{ __('client.dashboard.main_summary.rating_breakdown') }}
                                </p>
                                <div wire:ignore data-dashboard-chart="breakdown" class="client-dashboard-main-summary-breakdown mt-3 flex-1"></div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="client-dashboard-insights-row">
                    <section
                        @class([
                            'client-dashboard-survey-history-card',
                            'is-hours-grouping' => $survey_history_grouping === 'hours',
                        ])
                        data-dashboard-section="survey-history"
                        data-dashboard-history-chart
                        wire:key="survey-history-{{ $range_type }}-{{ $survey_history_grouping }}-{{ $date_from ?? 'empty' }}-{{ $date_to ?? 'empty' }}"
                    >
                        <script type="application/json" data-dashboard-history-config>
                            {!! json_encode($surveyHistoryChartConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
                        </script>

                        <div class="client-dashboard-survey-history-header">
                            <div class="client-dashboard-survey-history-title">
                                <span class="client-dashboard-survey-history-icon">
                                    <x-filament::icon icon="heroicon-m-information-circle" class="h-4 w-4" />
                                </span>
                                <span>{{ __('client.dashboard.survey_history.heading') }}</span>
                            </div>

                            <div class="client-dashboard-survey-history-actions">
                                <span>{{ __('client.dashboard.survey_history.show_by') }}:</span>
                                @unless ($forceSurveyHistoryHours)
                                    <button
                                        type="button"
                                        wire:click="setSurveyHistoryGrouping('range')"
                                        @class(['client-dashboard-survey-history-pill', 'is-active' => $survey_history_grouping === 'range'])
                                    >
                                        {{ __('client.dashboard.survey_history.range') }}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="setSurveyHistoryGrouping('days')"
                                        @class(['client-dashboard-survey-history-pill', 'is-active' => $survey_history_grouping === 'days'])
                                    >
                                        {{ __('client.dashboard.survey_history.days') }}
                                    </button>
                                @endunless
                                <button
                                    type="button"
                                    wire:click="setSurveyHistoryGrouping('hours')"
                                    @class(['client-dashboard-survey-history-pill', 'is-active' => $forceSurveyHistoryHours || $survey_history_grouping === 'hours'])
                                >
                                    {{ __('client.dashboard.survey_history.hours') }}
                                </button>
                            </div>
                        </div>

                        <div class="client-dashboard-survey-history-body">
                            <div wire:ignore data-dashboard-chart="survey-history" class="client-dashboard-survey-history-chart"></div>
                        </div>
                    </section>

                <section class="client-dashboard-employee-ranking-card" data-dashboard-section="employee-ranking">
                    <div class="client-dashboard-employee-ranking-header">
                        <h3>{{ __('client.dashboard.employee_ranking.heading') }}</h3>

                        <span class="client-dashboard-employee-ranking-order">
                            <x-filament::icon icon="heroicon-m-funnel" class="h-3.5 w-3.5" />
                            {{ __('client.dashboard.employee_ranking.order_label') }}:
                            {{ __('client.dashboard.employee_ranking.order_best_rated') }}
                        </span>
                    </div>

                    <div class="client-dashboard-employee-ranking-body">
                        @if (count($employeeRanking) > 0)
                            <div class="client-dashboard-employee-ranking-scroll">
                                @foreach ($employeeRanking as $employee)
                                    <article
                                        @class([
                                            'client-dashboard-employee-row',
                                            'is-inactive' => ! ($employee['is_active'] ?? true),
                                        ])
                                        wire:click="openEmployeeDetail('{{ $employee['id'] }}')"
                                        wire:keydown.enter="openEmployeeDetail('{{ $employee['id'] }}')"
                                        role="button"
                                        tabindex="0"
                                        aria-label="{{ __('client.dashboard.employee_ranking.detail_title') }}: {{ $employee['name'] }}"
                                    >
                                        <span class="client-dashboard-employee-info-tab" aria-hidden="true">
                                            <span class="client-dashboard-employee-info-tab-text">
                                                {{ __('client.dashboard.employee_ranking.info_tab') }}
                                            </span>
                                        </span>

                                        <div class="client-dashboard-employee-identity">
                                            <div class="client-dashboard-employee-avatar">
                                                @if ($employee['photo_url'])
                                                    <img src="{{ $employee['photo_url'] }}" alt="{{ $employee['name'] }}">
                                                @else
                                                    {{ $employee['initials'] }}
                                                @endif
                                            </div>

                                            <div class="client-dashboard-employee-name-wrap">
                                                <h4 class="client-dashboard-employee-name">
                                                    {{ $employee['name'] }}
                                                </h4>

                                                @unless ($employee['is_active'] ?? true)
                                                    <span class="client-dashboard-employee-inactive-badge">
                                                        {{ __('client.dashboard.employee_ranking.inactive') }}
                                                    </span>
                                                @endunless
                                            </div>
                                        </div>

                                        <div class="client-dashboard-employee-chart">
                                            @php
                                                $ratingGroups = [
                                                    1, 2, 3, 4, 5,
                                                ];
                                            @endphp

                                            <div class="client-dashboard-employee-bars" aria-label="{{ __('client.dashboard.employee_ranking.rating_distribution') }}">
                                                @foreach ($ratingGroups as $score)
                                                    @php
                                                        $count = (int) ($employee['score_counts'][$score] ?? 0);
                                                        $percentage = (float) ($employee['score_percentages'][$score] ?? 0);
                                                    @endphp
                                                    <span
                                                        class="client-dashboard-employee-bar"
                                                        title="{{ $score }}/5: {{ $count }}"
                                                        style="height: max(2px, {{ $percentage }}%); background-color: {{ $scoreColors[$score] }};"
                                                    ></span>
                                                @endforeach
                                            </div>

                                            <div class="client-dashboard-employee-bar-labels">
                                                @foreach ($ratingGroups as $score)
                                                    <span>{{ (int) round((float) ($employee['score_percentages'][$score] ?? 0)) }}%</span>
                                                @endforeach
                                            </div>
                                        </div>

                                        <dl class="client-dashboard-employee-surveys">
                                            <dt>{{ __('client.dashboard.employee_ranking.surveys') }}</dt>
                                            <dd>{{ $employee['surveys'] }}</dd>
                                        </dl>

                                        <div class="client-dashboard-employee-score">
                                            <span>{{ $employee['avg_score'] }}<small>/5</small></span>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-8 text-center dark:border-gray-700 dark:bg-gray-900">
                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                                    <x-filament::icon icon="heroicon-o-user-group" class="h-5 w-5" />
                                </div>
                                <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ __('client.dashboard.employee_ranking.empty') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </section>
                </div>

                <div class="client-dashboard-insights-row">
                <section
                    @class([
                        'client-dashboard-survey-history-card client-dashboard-score-trend-card',
                        'is-detail-open' => $showScoreTrendDetail,
                    ])
                    data-dashboard-section="score-trend"
                    data-dashboard-trend-chart
                    wire:key="score-trend-{{ $range_type }}-{{ $date_from ?? 'empty' }}-{{ $date_to ?? 'empty' }}"
                >
                    <script type="application/json" data-dashboard-trend-config>
                        {!! json_encode($scoreTrendChartConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
                    </script>

                    <div class="client-dashboard-survey-history-header">
                        <div class="client-dashboard-survey-history-title">
                            <span class="client-dashboard-survey-history-icon">
                                <x-filament::icon icon="heroicon-m-information-circle" class="h-4 w-4" />
                            </span>
                            <span>{{ __('client.dashboard.score_trend.heading') }}</span>
                        </div>

                        <div class="client-dashboard-survey-history-actions">
                            <button
                                type="button"
                                wire:click="openScoreTrendDetail"
                                @class(['client-dashboard-survey-history-pill', 'is-active' => $showScoreTrendDetail])
                            >
                                {{ __('client.dashboard.score_trend.show_detail') }}
                            </button>
                        </div>
                    </div>

                    <div class="client-dashboard-survey-history-body">
                        <div wire:ignore data-dashboard-chart="score-trend" class="client-dashboard-survey-history-chart"></div>
                    </div>

                    @if ($showScoreTrendDetail)
                        <div class="client-dashboard-score-trend-detail-backdrop" wire:click.self="closeScoreTrendDetail">
                            <div class="client-dashboard-score-trend-detail-modal" role="dialog" aria-modal="true" aria-labelledby="score-trend-detail-title">
                                <div class="client-dashboard-score-trend-detail-header">
                                    <h4 id="score-trend-detail-title" class="client-dashboard-score-trend-detail-title">
                                        {{ __('client.dashboard.score_trend.detail_modal_title', ['metric' => __('client.dashboard.score_trend.detail_metric')]) }}
                                    </h4>

                                    <button
                                        type="button"
                                        wire:click="closeScoreTrendDetail"
                                        class="client-dashboard-score-trend-detail-close"
                                        aria-label="{{ __('common.actions.close') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
                                    </button>
                                </div>

                                @if (count($scoreTrend['rows']) > 0)
                                    <div class="client-dashboard-score-trend-detail-table-wrap">
                                        <table class="client-dashboard-score-trend-detail-table">
                                            <thead>
                                                <tr>
                                                    <th scope="col">{{ __('client.dashboard.score_trend.detail_date') }}</th>
                                                    <th scope="col">{{ __('client.dashboard.score_trend.detail_surveys') }}</th>
                                                    <th scope="col">{{ __('client.dashboard.score_trend.detail_average') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($scoreTrend['rows'] as $row)
                                                    <tr>
                                                        <td>{{ $row['label'] }}</td>
                                                        <td>{{ number_format($row['count'], 0, ',', ' ') }}</td>
                                                        <td>
                                                            {{ $row['average'] !== null
                                                                ? number_format((float) $row['average'], 2, ',', ' ')
                                                                : __('common.placeholders.empty') }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="client-dashboard-score-trend-detail-empty">
                                        {{ __('client.dashboard.score_trend.detail_empty') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif
                </section>

                <section class="client-dashboard-improvement-ranking-card" data-dashboard-section="improvement-ranking">
                    <div class="client-dashboard-improvement-ranking-header">
                        <h3>{{ __('client.dashboard.improvement_ranking.heading') }}</h3>

                        <span class="client-dashboard-improvement-ranking-order">
                            <x-filament::icon icon="heroicon-m-funnel" class="h-3.5 w-3.5" />
                            {{ __('client.dashboard.improvement_ranking.order_label') }}:
                            {{ __('client.dashboard.improvement_ranking.order_negative') }}
                        </span>
                    </div>

                    <div class="client-dashboard-improvement-ranking-body">
                        <p class="client-dashboard-improvement-ranking-question">
                            <strong>{{ __('client.dashboard.employee_ranking.question_label') }}:</strong>
                            {{ $improvementRanking['question'] }}
                        </p>

                        @if (count($improvementRanking['options']) > 0)
                            <div class="client-dashboard-improvement-ranking-scroll">
                                @foreach ($improvementRanking['options'] as $option)
                                    <button
                                        type="button"
                                        wire:click="openImprovementDetail('{{ $option['id'] }}')"
                                        @class([
                                            'client-dashboard-improvement-row',
                                            'is-inactive' => ! ($option['is_active'] ?? true),
                                        ])
                                    >
                                        <span class="client-dashboard-improvement-info-tab" aria-hidden="true">
                                            <span class="client-dashboard-improvement-info-tab-text">
                                                {{ __('client.dashboard.employee_ranking.info_tab') }}
                                            </span>
                                        </span>

                                        <div class="client-dashboard-improvement-label">
                                            <span>
                                                {{ $option['label'] }}
                                                @unless ($option['is_active'] ?? true)
                                                    <span class="client-dashboard-improvement-inactive-badge">
                                                        {{ __('client.dashboard.improvement_ranking.deleted_option_badge') }}
                                                    </span>
                                                @endunless
                                            </span>
                                        </div>

                                        <div class="client-dashboard-improvement-negative">
                                            <div class="client-dashboard-improvement-negative-title">
                                                {{ __('client.dashboard.improvement_ranking.negative_ratings') }}
                                            </div>
                                            <div class="client-dashboard-improvement-negative-value">
                                                {{ number_format((float) $option['percentage'], 0, ',', ' ') }}%
                                            </div>
                                            <div class="client-dashboard-improvement-negative-count">
                                                {{ trans_choice('client.dashboard.improvement_ranking.surveys_count', $option['count'], ['count' => $option['count']]) }}
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>

                            <p class="client-dashboard-improvement-footnote">
                                {{ __('client.dashboard.improvement_ranking.percentage_note') }}
                            </p>
                        @else
                            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-8 text-center dark:border-gray-700 dark:bg-gray-900">
                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-5 w-5" />
                                </div>
                                <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ __('client.dashboard.improvement_ranking.empty') }}
                                </p>
                            </div>
                        @endif

                    </div>
                </section>

                @if ($showImprovementDetail && $improvementDetail)
                    <div
                        class="client-dashboard-improvement-detail-backdrop"
                        wire:click.self="closeImprovementDetail"
                        wire:key="improvement-detail-{{ $improvementDetail['id'] }}-{{ $range_type }}-{{ $date_from ?? 'empty' }}-{{ $date_to ?? 'empty' }}"
                    >
                        <div
                            class="client-dashboard-improvement-detail-modal"
                            data-dashboard-improvement-detail
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="improvement-detail-title"
                        >
                            <script type="application/json" data-dashboard-improvement-detail-config>
                                {!! json_encode($improvementDetail['chart_config'], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
                            </script>

                            <div class="client-dashboard-improvement-detail-header">
                                <div>
                                    <h4 id="improvement-detail-title" class="client-dashboard-improvement-detail-title">
                                        {{ __('client.dashboard.improvement_ranking.detail_title') }}
                                    </h4>

                                    <div class="client-dashboard-improvement-detail-meta">
                                        <span>{{ $improvementDetail['label'] }}</span>
                                        @unless ($improvementDetail['is_active'] ?? true)
                                            <span class="client-dashboard-improvement-inactive-badge">
                                                {{ __('client.dashboard.improvement_ranking.deleted_option_badge') }}
                                            </span>
                                        @endunless
                                        <span>{{ $improvementDetail['period_label'] }}</span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    wire:click="closeImprovementDetail"
                                    class="client-dashboard-improvement-detail-close"
                                    aria-label="{{ __('common.actions.close') }}"
                                >
                                    <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="client-dashboard-improvement-detail-body">
                                <div class="client-dashboard-improvement-detail-chart-panel">
                                    <h5 class="client-dashboard-improvement-detail-chart-title">
                                        {{ __('client.dashboard.improvement_ranking.detail_chart_title') }}
                                    </h5>

                                    <p class="client-dashboard-improvement-detail-note">
                                        {{ __('client.dashboard.improvement_ranking.detail_note') }}
                                    </p>

                                    <div wire:ignore data-dashboard-chart="improvement-detail" class="client-dashboard-improvement-detail-chart"></div>
                                </div>

                                <aside class="client-dashboard-improvement-detail-employees">
                                    <h5 class="client-dashboard-improvement-detail-employees-title">
                                        {{ __('client.dashboard.improvement_ranking.detail_employees_title') }}
                                    </h5>

                                    <div class="client-dashboard-improvement-detail-employee-list">
                                        @forelse ($improvementDetail['employee_ranking'] as $employee)
                                            <article
                                                @class([
                                                    'client-dashboard-improvement-detail-employee',
                                                    'is-inactive' => ! ($employee['is_active'] ?? true),
                                                ])
                                            >
                                                <span class="client-dashboard-improvement-detail-employee-avatar">
                                                    @if ($employee['photo_url'])
                                                        <img src="{{ $employee['photo_url'] }}" alt="{{ $employee['name'] }}">
                                                    @else
                                                        {{ $employee['initials'] }}
                                                    @endif
                                                </span>

                                                <div class="client-dashboard-improvement-detail-employee-main">
                                                    <p class="client-dashboard-improvement-detail-employee-name">
                                                        {{ $employee['name'] }}
                                                    </p>
                                                    @unless ($employee['is_active'] ?? true)
                                                        <span class="client-dashboard-employee-inactive-badge">
                                                            {{ __('client.dashboard.employee_ranking.inactive') }}
                                                        </span>
                                                    @endunless
                                                    <div class="client-dashboard-improvement-detail-employee-bar" aria-hidden="true">
                                                        <span style="width: {{ min(100, max(0, (float) $employee['percentage'])) }}%;"></span>
                                                    </div>
                                                </div>

                                                <div class="client-dashboard-improvement-detail-employee-count">
                                                    {{ number_format($employee['count'], 0, ',', ' ') }}
                                                    <small>{{ number_format((float) $employee['percentage'], 0, ',', ' ') }}%</small>
                                                </div>
                                            </article>
                                        @empty
                                            <p class="client-dashboard-improvement-detail-employee-empty">
                                                {{ __('client.dashboard.improvement_ranking.detail_employees_empty') }}
                                            </p>
                                        @endforelse
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </div>
                @endif
                </div>

                {{-- V2: charts, time series, recent activity, employee history and improvement-point detail. --}}
            </section>
        @elseif ($activeReputationTab === 'external')
            <section class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" data-dashboard-section="external-reputation">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                    <x-filament::icon icon="heroicon-o-sparkles" class="h-6 w-6" />
                </div>
                <h2 class="mt-4 text-base font-semibold text-gray-950 dark:text-white">
                    {{ __('client.dashboard.external.heading') }}
                </h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
                    {{ __('client.dashboard.external.description') }}
                </p>
            </section>
        @else
            <section class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" data-dashboard-section="sector-comparison">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="h-6 w-6" />
                </div>
                <h2 class="mt-4 text-base font-semibold text-gray-950 dark:text-white">
                    {{ __('client.dashboard.sector.heading') }}
                </h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
                    {{ __('client.dashboard.sector.description') }}
                </p>
            </section>
        @endif
    </div>

    @include('filament.components.client-dashboard.employee-detail-modal')

    @once
        @include('filament.components.client-dashboard-charts-script')
    @endonce
    </div>
</x-filament-panels::page>
