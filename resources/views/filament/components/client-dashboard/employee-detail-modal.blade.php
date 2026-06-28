@php
    $employeeDetail = $this->getSelectedEmployeeDetail();
@endphp

@if ($showEmployeeDetail && $employeeDetail)
    <div
        class="client-dashboard-employee-detail-backdrop"
        wire:click.self="closeEmployeeDetail"
        wire:key="employee-detail-{{ $employeeDetail['id'] }}-{{ $range_type }}-{{ $date_from ?? 'empty' }}-{{ $date_to ?? 'empty' }}"
    >
        <div
            class="client-dashboard-employee-detail-modal"
            data-dashboard-employee-detail
            role="dialog"
            aria-modal="true"
            aria-labelledby="employee-detail-title"
        >
            <script type="application/json" data-dashboard-employee-trend-config>
                {!! json_encode($employeeDetail['trend_chart_config'], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
            </script>

            <script type="application/json" data-dashboard-employee-satisfied-config>
                {!! json_encode($employeeDetail['satisfied_chart_config'], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
            </script>

            <div class="client-dashboard-employee-detail-header">
                <div class="client-dashboard-employee-detail-heading">
                    <h4 id="employee-detail-title" class="client-dashboard-employee-detail-title">
                        {{ __('client.dashboard.employee_ranking.detail_title') }}
                    </h4>

                    <p class="client-dashboard-employee-detail-subtitle">
                        <strong>{{ $employeeDetail['name'] }}</strong>
                    </p>

                    <p class="client-dashboard-employee-detail-period">
                        {{ $employeeDetail['period_label'] }}
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="closeEmployeeDetail"
                    class="client-dashboard-employee-detail-close"
                    aria-label="{{ __('common.actions.close') }}"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                </button>
            </div>

            <div class="client-dashboard-employee-detail-summary">
                <div class="client-dashboard-employee-detail-metric">
                    <p class="client-dashboard-employee-detail-metric-label">
                        {{ __('client.dashboard.employee_ranking.detail_avg_score') }}
                    </p>
                    <div class="client-dashboard-employee-detail-metric-value">
                        <div
                            class="client-dashboard-employee-detail-score"
                            style="border-color: {{ $employeeDetail['gauge_color'] }};"
                        >
                            <span>{{ $employeeDetail['avg_score'] }}<small>/5</small></span>
                        </div>
                    </div>
                </div>

                <div class="client-dashboard-employee-detail-metric">
                    <p class="client-dashboard-employee-detail-metric-label">
                        {{ __('client.dashboard.employee_ranking.detail_surveys_count') }}
                    </p>
                    <div class="client-dashboard-employee-detail-metric-value">
                        <div class="client-dashboard-employee-detail-surveys-value">
                            {{ number_format($employeeDetail['surveys'], 0, ',', ' ') }}
                        </div>
                    </div>
                </div>

                <div class="client-dashboard-employee-detail-metric client-dashboard-employee-detail-metric-satisfied">
                    <p class="client-dashboard-employee-detail-metric-label">
                        {{ __('client.dashboard.employee_ranking.detail_satisfied_customers') }}
                    </p>
                    <div class="client-dashboard-employee-detail-metric-value">
                        <div wire:ignore data-dashboard-chart="employee-satisfied" class="client-dashboard-employee-detail-satisfied-chart"></div>
                    </div>
                </div>

                <div class="client-dashboard-employee-detail-metric">
                    <p class="client-dashboard-employee-detail-metric-label">
                        {{ __('client.dashboard.employee_ranking.detail_ratings') }}
                    </p>
                    <div class="client-dashboard-employee-detail-metric-value">
                        <div class="client-dashboard-employee-detail-mini-chart">
                            <div class="client-dashboard-employee-bars" aria-hidden="true">
                                @foreach ($employeeDetail['rating_groups'] as $group)
                                    <span
                                        class="client-dashboard-employee-bar"
                                        style="height: max(2px, {{ $group['percentage'] }}%); background-color: {{ $group['color'] }};"
                                    ></span>
                                @endforeach
                            </div>

                            <div class="client-dashboard-employee-bar-labels" aria-hidden="true">
                                @foreach ($employeeDetail['rating_groups'] as $group)
                                    <span>{{ (int) round($group['percentage']) }}%</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div @class([
                'client-dashboard-employee-detail-bottom',
                'has-improvement' => count($employeeDetail['improvement_points']) > 0,
            ])>
                <div class="client-dashboard-employee-detail-trend">
                    <h5 class="client-dashboard-employee-detail-trend-title">
                        {{ __('client.dashboard.score_trend.heading') }}
                    </h5>

                    <div wire:ignore data-dashboard-chart="employee-trend" class="client-dashboard-employee-detail-trend-chart"></div>
                </div>

                @if (count($employeeDetail['improvement_points']) > 0)
                    <aside class="client-dashboard-employee-detail-improvement">
                        <h5 class="client-dashboard-employee-detail-improvement-title">
                            {{ __('client.dashboard.employee_ranking.detail_improvement_points') }}
                        </h5>

                        <div class="client-dashboard-employee-detail-improvement-list">
                            @foreach ($employeeDetail['improvement_points'] as $point)
                                <article class="client-dashboard-employee-detail-improvement-card">
                                    <p class="client-dashboard-employee-detail-improvement-label">
                                        {{ $point['label'] }}
                                    </p>

                                    <div class="client-dashboard-employee-detail-improvement-stats">
                                        <span class="client-dashboard-employee-detail-improvement-percentage">
                                            {{ number_format((float) $point['percentage'], 0, ',', ' ') }}%
                                        </span>
                                        <span class="client-dashboard-employee-detail-improvement-count">
                                            {{ trans_choice('client.dashboard.improvement_ranking.surveys_count', $point['count'], ['count' => $point['count']]) }}
                                        </span>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </div>
@endif
