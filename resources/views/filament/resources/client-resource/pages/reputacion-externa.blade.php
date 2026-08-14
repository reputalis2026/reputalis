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
    @endphp

    <div class="external-reputation-page space-y-6">
        <style>
            .external-reputation-page {
                font-size: .875rem;
            }

            .external-reputation-card {
                display: flex;
                min-width: 0;
                overflow: hidden;
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
                    grid-template-columns: repeat(4, minmax(0, 1fr));
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

            .external-reputation-metric {
                border-radius: .75rem;
                background: #f8fafc;
                padding: .9rem 1rem;
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
            }

            .dark .external-reputation-metric-help {
                color: #94a3b8;
            }

            .external-reputation-star-row {
                display: flex;
                align-items: center;
                gap: .75rem;
                font-size: .875rem;
            }

            .external-reputation-star-track {
                height: .5rem;
                flex: 1;
                overflow: hidden;
                border-radius: 999px;
                background: #e2e8f0;
            }

            .dark .external-reputation-star-track {
                background: rgba(255, 255, 255, .1);
            }

            .external-reputation-star-fill {
                height: 100%;
                border-radius: 999px;
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

        {{-- Filtros de periodo --}}
        <section class="external-reputation-card">
            <div class="external-reputation-card-header">
                <div class="external-reputation-card-title">
                    <span class="external-reputation-card-icon">
                        <x-filament::icon icon="heroicon-m-calendar-days" class="h-4 w-4" />
                    </span>
                    <span>{{ __('client.external_reputation.period_heading') }}</span>
                </div>

                <div class="external-reputation-card-actions">
                    @foreach (['days' => __('client.external_reputation.mode_days'), 'month' => __('client.external_reputation.mode_month'), 'year' => __('client.external_reputation.mode_year')] as $mode => $label)
                        <button
                            type="button"
                            wire:click="setHistoryMode('{{ $mode }}')"
                            @class(['external-reputation-pill', 'is-active' => $this->historyMode === $mode])
                        >{{ $label }}</button>
                    @endforeach

                    @if ($this->historyMode !== 'days')
                        <select wire:model.live="filterYear" class="external-reputation-select">
                            @foreach ($this->getAvailableYears() as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if ($this->historyMode === 'month')
                        <select wire:model.live="filterMonth" class="external-reputation-select">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                    @endif
                </div>
            </div>

            <div class="external-reputation-card-body">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('client.external_reputation.last_sync_label') }}:
                    {{ $client->external_reputation_last_synced_at?->timezone('Europe/Madrid')->format('d/m/Y H:i') ?? __('client.external_reputation.never_synced') }}
                </p>
            </div>
        </section>

        {{-- Estado actual --}}
        <section class="external-reputation-card">
            <div class="external-reputation-card-header">
                <div class="external-reputation-card-title">
                    <span class="external-reputation-card-icon">
                        <x-filament::icon icon="heroicon-m-star" class="h-4 w-4" />
                    </span>
                    <span>{{ __('client.external_reputation.current_heading') }}</span>
                </div>
            </div>

            <div class="external-reputation-card-body space-y-5">
                @if (! $snapshot)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('client.external_reputation.no_snapshot') }}
                    </p>
                @else
                    <div class="external-reputation-metrics-grid">
                        <div class="external-reputation-metric">
                            <p class="external-reputation-metric-label">{{ __('client.external_reputation.google_rating') }}</p>
                            <p class="external-reputation-metric-value">{{ $snapshot->rating ?? '—' }}</p>
                        </div>
                        <div class="external-reputation-metric">
                            <p class="external-reputation-metric-label">{{ __('client.external_reputation.reviews_total') }}</p>
                            <p class="external-reputation-metric-value">{{ number_format((int) $snapshot->reviews_total, 0, ',', '.') }}</p>
                        </div>
                        <div class="external-reputation-metric">
                            <p class="external-reputation-metric-label">{{ __('client.external_reputation.calculated_rating') }}</p>
                            <p class="external-reputation-metric-value">{{ $snapshot->calculated_rating ?? '—' }}</p>
                            <p class="external-reputation-metric-help">{{ __('client.external_reputation.calculated_rating_help') }}</p>
                        </div>
                        <div class="external-reputation-metric">
                            <p class="external-reputation-metric-label">{{ __('client.external_reputation.projection_heading') }}</p>
                            <p class="mt-2 text-sm font-semibold text-gray-950 dark:text-white">
                                {{ __('client.external_reputation.projection_text', [
                                    'n' => $starsNeeded ?? '—',
                                    'target' => number_format($this->targetRating, 1, ',', ''),
                                ]) }}
                            </p>
                            <p class="external-reputation-metric-help">{{ __('client.external_reputation.projection_disclaimer') }}</p>
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('client.external_reputation.stars_breakdown') }}</p>
                        @foreach ([5, 4, 3, 2, 1] as $star)
                            @php $count = (int) $snapshot->{"stars_{$star}"}; @endphp
                            <div class="external-reputation-star-row">
                                <span class="w-10 shrink-0 text-gray-600 dark:text-gray-300">{{ $star }}★</span>
                                <div class="external-reputation-star-track">
                                    <div
                                        class="external-reputation-star-fill"
                                        style="width: {{ min(100, ($count / $maxStars) * 100) }}%; background: {{ $scoreColors[$star] }};"
                                    ></div>
                                </div>
                                <span class="w-14 shrink-0 text-right tabular-nums text-gray-700 dark:text-gray-200">{{ number_format($count, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

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
                <div class="external-reputation-card-header">
                    <div class="external-reputation-card-title">
                        <span class="external-reputation-card-icon">
                            <x-filament::icon icon="heroicon-m-chart-bar" class="h-4 w-4" />
                        </span>
                        <span>{{ __('client.external_reputation.chart_rating_title') }}</span>
                    </div>
                </div>
                <div class="external-reputation-card-body">
                    <div wire:ignore data-external-reputation-chart="rating" class="external-reputation-chart"></div>
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
                <div class="external-reputation-card-header">
                    <div class="external-reputation-card-title">
                        <span class="external-reputation-card-icon">
                            <x-filament::icon icon="heroicon-m-presentation-chart-line" class="h-4 w-4" />
                        </span>
                        <span>{{ __('client.external_reputation.chart_total_title') }}</span>
                    </div>
                </div>
                <div class="external-reputation-card-body">
                    <div wire:ignore data-external-reputation-chart="total" class="external-reputation-chart"></div>
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

    @include('filament.components.external-reputation-charts-script')
</x-filament-panels::page>
