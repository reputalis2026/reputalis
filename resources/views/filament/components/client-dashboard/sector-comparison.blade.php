@php
    $comparison = $comparison ?? [];
    $scopes = $comparison['scopes'] ?? [];
@endphp

<section class="reputalis-sector-page" data-dashboard-section="sector-comparison" data-sector-comparison>
    <div class="reputalis-sector-hero">
        <h1>{{ $comparison['heading'] ?? __('client.dashboard.sector.heading') }}</h1>
        <p>{{ $comparison['subheading'] ?? __('client.dashboard.sector.subheading') }}</p>
        @if (! empty($comparison['fake']))
            <span class="reputalis-sector-fake-badge">{{ __('client.dashboard.sector.fake_badge') }}</span>
        @endif
    </div>

    <div class="reputalis-sector-scopes">
        @foreach ($scopes as $scope)
            @php
                $chartKey = $scope['key'] ?? $loop->index;
                $isScatter = ($scope['chart_type'] ?? '') === 'scatter';
                $scopeIndex = $loop->iteration;
            @endphp
            <article
                class="reputalis-sector-card"
                data-sector-scope="{{ $chartKey }}"
                wire:key="sector-scope-{{ $chartKey }}"
            >
                <script type="application/json" data-sector-scope-config>
                    {!! json_encode($scope['charts'] ?? [], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
                </script>

                <header class="reputalis-sector-card-title">
                    <span class="reputalis-sector-card-index" aria-hidden="true">{{ $scopeIndex }}</span>
                    <span>{{ $scope['title'] }}</span>
                </header>

                <div class="reputalis-sector-kpis">
                    <div class="reputalis-sector-kpi">
                        <span class="reputalis-sector-kpi-label">{{ __('client.dashboard.sector.kpi_rating') }}</span>
                        <strong class="reputalis-sector-kpi-value">{{ number_format((float) $scope['your_rating'], 1, ',', '') }}</strong>
                    </div>
                    <div class="reputalis-sector-kpi">
                        <span class="reputalis-sector-kpi-label">{{ $scope['average_label'] ?? __('client.dashboard.sector.kpi_average') }}</span>
                        <strong class="reputalis-sector-kpi-value">{{ number_format((float) $scope['market_average'], 2, ',', '') }}</strong>
                    </div>
                    <div class="reputalis-sector-kpi">
                        <span class="reputalis-sector-kpi-label">{{ __('client.dashboard.sector.kpi_analyzed') }}</span>
                        <strong class="reputalis-sector-kpi-value">{{ number_format((int) $scope['analyzed'], 0, ',', '.') }}</strong>
                    </div>
                    <div class="reputalis-sector-kpi reputalis-sector-kpi--position">
                        <span class="reputalis-sector-kpi-label">{{ __('client.dashboard.sector.kpi_position') }}</span>
                        <strong class="reputalis-sector-kpi-value">
                            {{ __('client.dashboard.sector.position_of', [
                                'position' => $scope['position'],
                                'total' => $scope['analyzed'],
                            ]) }}
                        </strong>
                    </div>
                    <div class="reputalis-sector-kpi reputalis-sector-kpi--ahead">
                        <span class="reputalis-sector-kpi-label">{{ __('client.dashboard.sector.kpi_ahead') }}</span>
                        <strong class="reputalis-sector-kpi-value">{{ $scope['ahead'] }}</strong>
                    </div>
                    <div class="reputalis-sector-kpi reputalis-sector-kpi--behind">
                        <span class="reputalis-sector-kpi-label">{{ __('client.dashboard.sector.kpi_behind') }}</span>
                        <strong class="reputalis-sector-kpi-value">{{ $scope['behind'] }}</strong>
                    </div>
                </div>

                @if (! empty($scope['projection']))
                    <div class="reputalis-sector-banner">
                        {{ __('client.dashboard.sector.projection_before') }}
                        <strong>{{ $scope['projection']['five_stars'] }}</strong>
                        {{ __('client.dashboard.sector.projection_mid') }}
                        <strong>{{ __('client.dashboard.sector.ordinal', ['n' => $scope['projection']['target_position']]) }}</strong>
                    </div>
                @endif

                <div class="reputalis-sector-charts">
                    <div class="reputalis-sector-chart-panel">
                        <h3>
                            {{ $isScatter
                                ? __('client.dashboard.sector.chart_scatter')
                                : __('client.dashboard.sector.chart_distribution') }}
                        </h3>
                        <div
                            wire:ignore
                            class="reputalis-sector-chart"
                            data-sector-chart="{{ $isScatter ? 'scatter' : 'histogram' }}"
                        ></div>
                    </div>
                    <div class="reputalis-sector-chart-panel">
                        <h3>{{ __('client.dashboard.sector.chart_evolution') }}</h3>
                        <div
                            wire:ignore
                            class="reputalis-sector-chart"
                            data-sector-chart="evolution"
                        ></div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
