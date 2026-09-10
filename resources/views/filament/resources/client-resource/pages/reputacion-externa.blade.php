<x-filament-panels::page>
    @php
        $client = $this->getClientRecord();
        $snapshot = $this->getLatestSnapshot();
        $starsNeeded = $this->getFiveStarsNeeded();
        $alerts = $this->getRecentAlerts();
        $unreadAlerts = $this->getUnreadAlertsCount();
        $history = $this->getHistoryRows();
        $chartConfig = $this->getHistoryChartConfig();
        $maxStars = $snapshot ? max(1, max($snapshot->starsBreakdown())) : 1;
        $scoreColors = [
            1 => '#FF3901',
            2 => '#FF9880',
            3 => '#FFC60F',
            4 => '#8DFFA8',
            5 => '#01FF01',
        ];
        $rangeKey = $chartConfig['range_key'];
        $scoreColorsList = ['#FF3901', '#FF9880', '#FFC60F', '#8DFFA8', '#01FF01'];
        $ratingRaw = $snapshot ? (float) ($snapshot->rating ?? 0) : 0;
        $totalReviews = $snapshot ? (int) $snapshot->reviews_total : 0;
        $breakdownConfig = $snapshot ? [
            'scoreLabels' => ['1', '2', '3', '4', '5'],
            'scorePercentages' => collect([1,2,3,4,5])->map(fn ($s) => $totalReviews > 0 ? round(((int) $snapshot->{"stars_{$s}"} / $totalReviews) * 100, 1) : 0)->values()->all(),
            'scoreCounts' => collect([1,2,3,4,5])->map(fn ($s) => (int) $snapshot->{"stars_{$s}"})->values()->all(),
            'scoreColors' => $scoreColorsList,
            'labelColor' => '#6b7280',
            'surveysTooltipLabel' => __('client.external_reputation.reviews_total') . ':',
        ] : null;
        $realRating = ($snapshot && $totalReviews > 0)
            ? round(collect([1,2,3,4,5])->sum(fn ($s) => $s * (int) $snapshot->{"stars_{$s}"}) / $totalReviews, 2)
            : 0;
        $makeGaugeConfig = fn (float $value, int $decimals = 1) => [
            'gaugePercent' => $value > 0 ? round(($value / 5) * 100, 1) : 0,
            'gaugeColor' => match (true) {
                $value === 0.0 => '#9ca3af',
                $value >= 4 => '#22c55e',
                $value >= 3 => '#f59e0b',
                default => '#ef4444',
            },
            'gaugeValue' => $snapshot ? number_format($value, $decimals, ',', '') : '—',
            'gaugeLabel' => __('client.dashboard.main_summary.out_of_five'),
            'trackColor' => '#e5e7eb',
            'labelColor' => '#6b7280',
        ];
        $gaugeGoogleConfig = $makeGaugeConfig($ratingRaw, 1);
        $gaugeRealConfig = $makeGaugeConfig($realRating, 2);
        $gaugeRealConfig['gaugeColor'] = '#3b82f6';
    @endphp

    <div class="external-reputation-page space-y-6">
        <style>
            .external-reputation-page {
                font-size: .875rem;
            }

            .external-reputation-card {
                display: flex;
                min-width: 0;
                overflow: visible;
                flex-direction: column;
                border-radius: .875rem;
                background: #ffffff;
                box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
                border: 1px solid rgba(15, 23, 42, .08);
            }

            .dark .external-reputation-card {
                background: rgb(17 24 39);
                border-color: rgba(255, 255, 255, .1);
            }

            .external-reputation-card-header {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
                padding: .9rem 1rem;
                border-bottom: 1px solid rgba(15, 23, 42, .06);
            }

            .dark .external-reputation-card-header {
                border-bottom-color: rgba(255, 255, 255, .08);
            }

            .external-reputation-card-title {
                display: inline-flex;
                align-items: center;
                gap: .55rem;
                font-size: .95rem;
                font-weight: 600;
                color: #0f172a;
            }

            .dark .external-reputation-card-title {
                color: #f8fafc;
            }

            .external-reputation-card-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 1.65rem;
                height: 1.65rem;
                border-radius: .5rem;
                background: #f1f5f9;
                color: #64748b;
            }

            .dark .external-reputation-card-icon {
                background: rgba(255, 255, 255, .06);
                color: #94a3b8;
            }

            .external-reputation-card-actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: .45rem;
            }

            .external-reputation-pill {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                border: 1px solid rgba(15, 23, 42, .12);
                background: #ffffff;
                color: #475569;
                padding: .28rem .7rem;
                font-size: .75rem;
                font-weight: 600;
                line-height: 1.2;
            }

            button.external-reputation-pill {
                appearance: none;
                cursor: pointer;
            }

            .external-reputation-pill.is-active {
                border-color: #f59e0b;
                background: #f59e0b;
                color: #ffffff;
            }

            .dark .external-reputation-pill {
                background: rgb(31 41 55);
                color: #cbd5e1;
                border-color: rgba(255, 255, 255, .12);
            }

            .external-reputation-card-body {
                padding: .85rem 1rem 1rem;
            }

            .external-reputation-chart-card .external-reputation-card-body {
                min-height: 17rem;
                flex: 1;
                padding: .65rem 1rem 1rem;
            }

            .external-reputation-chart {
                min-height: 14.5rem;
                overflow: visible;
            }

            .external-reputation-metrics-grid {
                display: grid;
                gap: .85rem;
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            @media (min-width: 640px) {
                .external-reputation-metrics-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1024px) {
                .external-reputation-metrics-grid {
                    grid-template-columns: minmax(0, .8fr) minmax(0, .8fr) minmax(0, 1.4fr);
                }

                .external-reputation-charts-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 1rem;
                    align-items: stretch;
                }

                .external-reputation-charts-grid > .external-reputation-chart-card:last-child {
                    grid-column: 1 / -1;
                }
            }

            .external-reputation-chart-filters {
                display: flex;
                gap: .25rem;
            }

            .external-reputation-chart-filter-btn {
                padding: .25rem .625rem;
                font-size: .75rem;
                font-weight: 500;
                border-radius: .375rem;
                border: 1px solid #e5e7eb;
                background: #fff;
                color: #6b7280;
                cursor: pointer;
                transition: all .15s ease;
                line-height: 1.25rem;
            }

            .external-reputation-chart-filter-btn:hover {
                background: #f3f4f6;
                color: #374151;
            }

            .external-reputation-chart-filter-btn.active {
                background: #059669;
                color: #fff;
                border-color: #059669;
            }

            .external-reputation-chart-filter-btn.disabled {
                opacity: .45;
                cursor: not-allowed;
            }

            .external-reputation-metric {
                border-radius: .75rem;
                background: #f8fafc;
                padding: .9rem 1rem;
            }

            .external-reputation-metric[data-external-reputation-chart-card="breakdown"] {
                overflow: visible;
                position: relative;
                z-index: 3;
            }

            .apexcharts-tooltip.reputalis-breakdown-tooltip {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                z-index: 60 !important;
                overflow: visible !important;
                pointer-events: none;
            }

            .apexcharts-tooltip.reputalis-breakdown-tooltip .apexcharts-tooltip-series-group {
                background: transparent !important;
                padding: 0 !important;
            }

            .dark .external-reputation-metric {
                background: rgba(255, 255, 255, .04);
            }

            .external-reputation-metric-label {
                font-size: .7rem;
                font-weight: 600;
                letter-spacing: .04em;
                text-transform: uppercase;
                color: #64748b;
            }

            .dark .external-reputation-metric-label {
                color: #94a3b8;
            }

            .external-reputation-metric-value {
                margin-top: .35rem;
                font-size: 1.55rem;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.15;
            }

            .dark .external-reputation-metric-value {
                color: #f8fafc;
            }

            .external-reputation-metric-help {
                margin-top: .35rem;
                font-size: .75rem;
                color: #64748b;
                text-align: center;
            }

            .dark .external-reputation-metric-help {
                color: #94a3b8;
            }


            .external-reputation-table-wrap {
                overflow-x: auto;
            }

            .external-reputation-table {
                min-width: 100%;
                text-align: left;
                font-size: .875rem;
            }

            .external-reputation-table thead th {
                border-bottom: 1px solid rgba(15, 23, 42, .08);
                padding: .55rem .5rem;
                font-size: .7rem;
                font-weight: 600;
                letter-spacing: .04em;
                text-transform: uppercase;
                color: #64748b;
            }

            .dark .external-reputation-table thead th {
                border-bottom-color: rgba(255, 255, 255, .1);
                color: #94a3b8;
            }

            .external-reputation-table tbody td {
                border-bottom: 1px solid rgba(15, 23, 42, .05);
                padding: .55rem .5rem;
                color: #1e293b;
                white-space: nowrap;
            }

            .dark .external-reputation-table tbody td {
                border-bottom-color: rgba(255, 255, 255, .06);
                color: #e2e8f0;
            }

            .external-reputation-select {
                border-radius: .65rem;
                border: 1px solid rgba(15, 23, 42, .12);
                background: #ffffff;
                color: #334155;
                font-size: .8rem;
                padding: .3rem .55rem;
            }

            .dark .external-reputation-select {
                border-color: rgba(255, 255, 255, .12);
                background: rgb(17 24 39);
                color: #e2e8f0;
            }

            @media (min-width: 1024px) {
                .external-reputation-row-1 {
                    display: grid;
                    grid-template-columns: minmax(14rem, 17rem) minmax(0, 1fr);
                    gap: 1rem;
                    align-items: start;
                }

                .external-reputation-row-estado-desglose {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 1rem;
                    align-items: start;
                }
            }

            .external-reputation-filter-header {
                background: #f39c12;
                color: #ffffff;
                padding: .5rem .75rem;
            }

            .external-reputation-filter-header h3 {
                margin: 0;
                font-size: .84rem;
                font-weight: 700;
                line-height: 1.25rem;
            }

            .client-dashboard-filter-surveys-highlight {
                margin-top: .15rem;
                padding: 1.1rem 1rem 1.15rem;
                border-radius: .8rem;
                background: rgba(10, 154, 185, .14);
                box-shadow: none;
                text-align: center;
            }

            .client-dashboard-filter-surveys-label {
                margin: 0;
                color: #0A9AB9;
                font-size: .64rem;
                font-weight: 700;
                letter-spacing: .05em;
                line-height: 1.2;
                text-transform: uppercase;
            }

            .client-dashboard-filter-surveys-value {
                margin: .4rem 0 0;
                color: #0A9AB9;
                font-size: 2.45rem;
                font-weight: 800;
                line-height: 1;
            }

            .external-reputation-gauge-metric {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .external-reputation-gauge-wrap {
                width: 100%;
                max-width: 10rem;
                margin-top: .25rem;
            }

        </style>

        @include('filament.components.client-dashboard.reputation-tabs', [
            'tabs' => $this->getReputationTabs(),
            'activeTab' => 'external',
        ])

        @unless (filled($client->google_place_id))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                <p class="font-medium">{{ __('client.external_reputation.missing_place_id_title') }}</p>
                <p class="mt-1">{{ __('client.external_reputation.missing_place_id_body') }}</p>
                @if ($this->canSync())
                    <a href="{{ $this->getEditClientUrl() }}" class="mt-2 inline-flex text-sm font-semibold text-primary-600 underline dark:text-primary-300">
                        {{ __('client.external_reputation.edit_client_link') }}
                    </a>
                @endif
            </div>
        @endunless

        @if ($client->external_reputation_last_error)
            <div class="rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-800 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-100">
                <p class="font-medium">{{ __('client.external_reputation.last_error_label') }}</p>
                <p class="mt-1 break-words">{{ $client->external_reputation_last_error }}</p>
            </div>
        @endif

        {{-- Estado actual --}}
        <section class="external-reputation-card" data-external-reputation-gauge-card>
            <script type="application/json" data-external-reputation-gauge-config="google">
                {!! json_encode($gaugeGoogleConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
            </script>
            <script type="application/json" data-external-reputation-gauge-config="real">
                {!! json_encode($gaugeRealConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
            </script>
            <div class="external-reputation-card-header" style="display:flex;align-items:center;gap:.75rem;">
                <div class="external-reputation-card-title" style="flex:1;">
                    <span class="external-reputation-card-icon">
                        <x-filament::icon icon="heroicon-m-star" class="h-4 w-4" />
                    </span>
                    <span>{{ __('client.external_reputation.current_heading') }}</span>
                </div>
                <div class="client-dashboard-filter-surveys-highlight" style="margin:0;padding:.35rem .75rem .3rem;flex-shrink:0;">
                    <p class="client-dashboard-filter-surveys-label">
                        {{ __('client.external_reputation.reviews_total') }}
                    </p>
                    <p class="client-dashboard-filter-surveys-value" style="font-size:1.6rem;">
                        {{ number_format($totalReviews, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="external-reputation-card-body space-y-5">
                @if (! $snapshot)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('client.external_reputation.no_snapshot') }}
                    </p>
                @else
                    <div class="external-reputation-metrics-grid">
                        <div class="external-reputation-metric external-reputation-gauge-metric">
                            <p class="external-reputation-metric-label">{{ __('client.external_reputation.google_rating') }}</p>
                            <div class="external-reputation-gauge-wrap">
                                <div wire:ignore data-external-reputation-chart="gauge-google"></div>
                            </div>
                            <p class="external-reputation-metric-label" style="margin-top:.75rem;">{{ __('client.external_reputation.calculated_rating') }}</p>
                            <div class="external-reputation-gauge-wrap">
                                <div wire:ignore data-external-reputation-chart="gauge-real"></div>
                            </div>
                        </div>
                        @php
                            $currentTruncated = round(floor(round($realRating, 4) * 10) / 10, 1);
                            $nextLevel = round($currentTruncated + 0.1, 1);
                            $nextLevelFmt = number_format($nextLevel, 1, ',', '');
                            $currentTruncatedFmt = number_format($currentTruncated, 1, ',', '');
                            $rawReal = ($totalReviews > 0)
                                ? collect([1,2,3,4,5])->sum(fn ($s) => $s * (int) $snapshot->{"stars_{$s}"}) / $totalReviews
                                : 0.0;
                            $diff = $rawReal - $currentTruncated;
                            $progressInRange = min(100, max(0, (int) (floor($diff / 0.1 * 100 / 5) * 5)));
                            $starsNeededNextLevel = $snapshot
                                ? \App\Support\ExternalReputation\RatingProjection::fiveStarsNeededForTarget($snapshot->starsBreakdown(), $nextLevel)
                                : null;
                            if ($starsNeededNextLevel !== null && $starsNeededNextLevel <= 0) $starsNeededNextLevel = 0;
                            $arcTotal = 173;
                            $arcProgress = round($progressInRange * $arcTotal / 100);
                            $angle = -180 + ($progressInRange / 100) * 180;
                            $rad = deg2rad($angle);
                            $cx = 70; $cy = 75; $r = 55;
                            $starX = round($cx + $r * cos($rad), 1);
                            $starY = round($cy + $r * sin($rad), 1);
                        @endphp
                        <div class="external-reputation-metric external-reputation-gauge-metric" style="justify-content:space-between;gap:.75rem;">
                            <div style="text-align:center;">
                            <p class="external-reputation-metric-label">{{ __('client.external_reputation.projection_progress_title', ['target' => $nextLevelFmt]) }} ⭐</p>
                            <div style="margin:.5rem auto 0;width:11rem;height:7.5rem;position:relative;">
                                <svg viewBox="0 0 140 95" style="width:100%;height:100%;overflow:visible;">
                                    <path d="M15,75 A55,55 0 0,1 125,75" fill="none" stroke="#f59e0b" stroke-width="14" stroke-linecap="round"/>
                                    @if($progressInRange > 0)
                                        <path d="M15,75 A55,55 0 0,1 125,75" fill="none" stroke="#22c55e" stroke-width="14" stroke-linecap="round"
                                              stroke-dasharray="{{ $arcProgress }} {{ $arcTotal }}" />
                                    @endif
                                    <text x="{{ $starX }}" y="{{ $starY }}" text-anchor="middle" dominant-baseline="central" font-size="14">⭐</text>
                                    <text x="70" y="68" text-anchor="middle" font-size="22" font-weight="800" fill="currentColor">{{ $progressInRange }}%</text>
                                    <text x="12" y="92" text-anchor="middle" font-size="11" font-weight="600" fill="#9ca3af">{{ $currentTruncatedFmt }}</text>
                                    <text x="128" y="92" text-anchor="middle" font-size="11" font-weight="600" fill="#9ca3af">{{ $nextLevelFmt }}</text>
                                </svg>
                            </div>
                            </div>
                            <div style="text-align:center;">
                            <p class="external-reputation-metric-label" style="margin-bottom:.5rem;">{{ __('client.external_reputation.projection_reviews_needed') }}</p>
                            <div style="margin-top:.5rem;padding:.7rem 1.2rem;border-radius:.75rem;background:linear-gradient(145deg, #f97316 0%, #fb923c 55%, #fdba74 100%);box-shadow:0 8px 20px rgba(249,115,22,.3);text-align:center;">
                                <p style="font-size:2.4rem;font-weight:800;line-height:1;color:#fff;margin:0;text-shadow:0 1px 3px rgba(0,0,0,.15);">{{ $starsNeededNextLevel ?? '—' }}</p>
                                <p style="font-size:.7rem;font-weight:600;color:rgba(255,255,255,.9);margin:.2rem 0 0;text-transform:uppercase;letter-spacing:.04em;">{{ __('client.external_reputation.projection_five_star') }}</p>
                            </div>
                            </div>
                        </div>
                        @if ($breakdownConfig)
                            <div
                                class="external-reputation-metric external-reputation-gauge-metric"
                                data-external-reputation-chart-card="breakdown"
                                wire:key="external-breakdown-{{ $rangeKey }}"
                            >
                                <script type="application/json" data-external-reputation-breakdown-config>
                                    {!! json_encode($breakdownConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
                                </script>
                                <p class="external-reputation-metric-label" style="margin-bottom:.25rem;">{{ __('client.external_reputation.stars_breakdown') }}</p>
                                <div wire:ignore data-external-reputation-chart="breakdown" style="min-height:10rem;"></div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </section>
        {{-- /Estado actual --}}

        {{-- Gráficos en cards independientes --}}
        <div class="external-reputation-charts-grid space-y-4 lg:space-y-0">
            <section
                class="external-reputation-card external-reputation-chart-card"
                data-external-reputation-chart-card="rating"
                wire:key="external-rating-chart-{{ $rangeKey }}"
            >
                <script type="application/json" data-external-reputation-chart-config>
                    {!! json_encode($chartConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
                </script>
                <div class="external-reputation-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                    <div class="external-reputation-card-title">
                        <span class="external-reputation-card-icon">
                            <x-filament::icon icon="heroicon-m-chart-bar" class="h-4 w-4" />
                        </span>
                        <span>{{ __('client.external_reputation.chart_rating_title') }}</span>
                    </div>
                    <div class="external-reputation-chart-filters">
                        @foreach (['days', 'month', 'year'] as $mode)
                            <button type="button" disabled class="external-reputation-chart-filter-btn disabled">{{ __("client.external_reputation.chart_filter_{$mode}") }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="external-reputation-card-body">
                    <div wire:ignore data-external-reputation-chart="rating" class="external-reputation-chart" style="min-height:15rem;"></div>
                </div>
            </section>

            <section
                class="external-reputation-card external-reputation-chart-card"
                data-external-reputation-chart-card="total"
                wire:key="external-total-chart-{{ $rangeKey }}"
            >
                <script type="application/json" data-external-reputation-chart-config>
                    {!! json_encode($chartConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
                </script>
                <div class="external-reputation-card-header" style="display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                    <div class="external-reputation-card-title">
                        <span class="external-reputation-card-icon">
                            <x-filament::icon icon="heroicon-m-presentation-chart-line" class="h-4 w-4" />
                        </span>
                        <span>{{ __('client.external_reputation.chart_total_title') }}</span>
                    </div>
                    <div class="external-reputation-chart-filters">
                        @foreach (['days', 'month', 'year'] as $mode)
                            <button type="button" disabled class="external-reputation-chart-filter-btn disabled">{{ __("client.external_reputation.chart_filter_{$mode}") }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="external-reputation-card-body">
                    <div wire:ignore data-external-reputation-chart="total" class="external-reputation-chart" style="min-height:15rem;"></div>
                </div>
            </section>

            <section
                class="external-reputation-card external-reputation-chart-card"
                data-external-reputation-chart-card="stars"
                wire:key="external-stars-chart-{{ $rangeKey }}"
            >
                <script type="application/json" data-external-reputation-chart-config>
                    {!! json_encode($chartConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
                </script>
                <div class="external-reputation-card-header">
                    <div class="external-reputation-card-title">
                        <span class="external-reputation-card-icon">
                            <x-filament::icon icon="heroicon-m-squares-2x2" class="h-4 w-4" />
                        </span>
                        <span>{{ __('client.external_reputation.chart_stars_title') }}</span>
                    </div>
                </div>
                <div class="external-reputation-card-body">
                    <div wire:ignore data-external-reputation-chart="stars" class="external-reputation-chart" style="min-height: 16rem;"></div>
                </div>
            </section>
        </div>

        {{-- Alertas --}}
        <section class="external-reputation-card">
            <div class="external-reputation-card-header">
                <div class="external-reputation-card-title">
                    <span class="external-reputation-card-icon">
                        <x-filament::icon icon="heroicon-m-bell-alert" class="h-4 w-4" />
                    </span>
                    <span>{{ __('client.external_reputation.alerts_heading') }}</span>
                    @if ($unreadAlerts > 0)
                        <span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-danger-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                            {{ $unreadAlerts }}
                        </span>
                    @endif
                </div>

                @if ($unreadAlerts > 0)
                    <div class="external-reputation-card-actions">
                        <button
                            type="button"
                            wire:click="markAllAlertsAsRead"
                            class="external-reputation-pill"
                        >
                            {{ __('client.external_reputation.alerts_mark_all_read') }}
                        </button>
                    </div>
                @endif
            </div>
            <div class="external-reputation-card-body">
                @if ($alerts->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('client.external_reputation.alerts_empty') }}
                    </p>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($alerts as $alert)
                            <li @class([
                                'flex flex-wrap items-start justify-between gap-3 py-3 text-sm',
                                'bg-danger-50/60 dark:bg-danger-500/5 -mx-2 px-2 rounded-lg' => $alert->isUnread() && $alert->isNegativeIncrease(),
                                'bg-amber-50/70 dark:bg-amber-500/5 -mx-2 px-2 rounded-lg' => $alert->isUnread() && $alert->isTotalDropAnomaly(),
                            ])>
                                <div class="min-w-0 space-y-1">
                                    <p class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $alert->detected_at?->timezone('Europe/Madrid')->format('d/m/Y H:i') }}
                                        —
                                        @if ($alert->isTotalDropAnomaly())
                                            {{ __('client.external_reputation.alert_total_drop', [
                                                'n' => abs((int) $alert->delta_reviews_total),
                                            ]) }}
                                        @else
                                            @if ($alert->delta_stars_1 > 0)
                                                +{{ $alert->delta_stars_1 }}×1★
                                            @endif
                                            @if ($alert->delta_stars_2 > 0)
                                                +{{ $alert->delta_stars_2 }}×2★
                                            @endif
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        @if ($alert->isTotalDropAnomaly())
                                            {{ __('client.external_reputation.alert_total_drop_help') }}
                                        @else
                                            {{ __('client.external_reputation.alert_negative_help') }}
                                        @endif
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    @if ($alert->isUnread())
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300' => $alert->isNegativeIncrease(),
                                            'bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200' => $alert->isTotalDropAnomaly(),
                                        ])>
                                            {{ $alert->isTotalDropAnomaly()
                                                ? __('client.external_reputation.alert_anomaly')
                                                : __('client.external_reputation.alert_unread') }}
                                        </span>
                                        <button
                                            type="button"
                                            wire:click="markAlertAsRead('{{ $alert->id }}')"
                                            class="external-reputation-pill"
                                        >
                                            {{ __('client.external_reputation.alert_mark_read') }}
                                        </button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        {{-- Tabla histórico --}}
        <section class="external-reputation-card">
            <div class="external-reputation-card-header">
                <div class="external-reputation-card-title">
                    <span class="external-reputation-card-icon">
                        <x-filament::icon icon="heroicon-m-table-cells" class="h-4 w-4" />
                    </span>
                    <span>{{ __('client.external_reputation.history_heading') }}</span>
                </div>
            </div>
            <div class="external-reputation-card-body">
                <div class="external-reputation-table-wrap">
                    <table class="external-reputation-table">
                        <thead>
                            <tr>
                                <th>{{ __('client.external_reputation.col_date') }}</th>
                                <th>{{ __('client.external_reputation.col_rating') }}</th>
                                <th>{{ __('client.external_reputation.col_calculated') }}</th>
                                <th>{{ __('client.external_reputation.col_total') }}</th>
                                <th>1★</th>
                                <th>2★</th>
                                <th>3★</th>
                                <th>4★</th>
                                <th>5★</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="tabular-nums">{{ $row['rating'] ?? '—' }}</td>
                                    <td class="tabular-nums">{{ $row['calculated_rating'] ?? '—' }}</td>
                                    <td class="tabular-nums">{{ number_format($row['reviews_total'], 0, ',', '.') }}</td>
                                    <td class="tabular-nums">{{ $row['stars_1'] }}</td>
                                    <td class="tabular-nums">{{ $row['stars_2'] }}</td>
                                    <td class="tabular-nums">{{ $row['stars_3'] }}</td>
                                    <td class="tabular-nums">{{ $row['stars_4'] }}</td>
                                    <td class="tabular-nums">{{ $row['stars_5'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-6 text-center text-gray-500 dark:text-gray-400">
                                        {{ __('client.external_reputation.history_empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @push('scripts')
        @include('filament.components.external-reputation-charts-script')
    @endpush
</x-filament-panels::page>
