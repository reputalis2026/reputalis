@php
    $rangeTypes = $rangeTypes ?? [];
    $activeRangeType = $activeRangeType ?? 'all';
    $isCustomRange = $isCustomRange ?? false;
    $summary = $summary ?? [];
    $scoreColors = $scoreColors ?? [
        1 => '#EE2737',
        2 => '#FF6A13',
        3 => '#FFB81C',
        4 => '#A4D65E',
        5 => '#00B140',
    ];

    $breakdownByScore = collect($summary['score_breakdown'] ?? [])->keyBy('score');
    $maxCount = max(1, (int) collect($summary['score_breakdown'] ?? [])->max('count'));
    $distributionBars = collect([1, 2, 3, 4, 5])->map(function (int $score) use ($breakdownByScore, $maxCount, $scoreColors): array {
        $count = (int) ($breakdownByScore[$score]['count'] ?? 0);

        return [
            'score' => $score,
            'count' => $count,
            'height' => $count > 0 ? max(18, ($count / $maxCount) * 100) : 10,
            'color' => $scoreColors[$score] ?? '#9ca3af',
        ];
    });

    $avgDeltaPositive = $summary['avg_delta_positive'] ?? null;
@endphp

<div
    class="reputalis-internal-hero-wrap"
    wire:key="internal-hero-{{ $activeRangeType }}-{{ $date_from ?? 'empty' }}-{{ $date_to ?? 'empty' }}"
>
    <div class="reputalis-internal-hero">
        <div class="reputalis-internal-hero-copy">
            <h1>{{ __('client.dashboard.internal.heading') }}</h1>
            <p>{{ __('client.dashboard.internal.realtime_subheading') }}</p>
        </div>

        <div class="reputalis-range-pills" role="tablist" aria-label="{{ __('client.dashboard.filters.range_type') }}">
            @foreach ($rangeTypes as $key => $label)
                <button
                    type="button"
                    role="tab"
                    wire:click="setRangeType('{{ $key }}')"
                    @class([
                        'reputalis-range-pill',
                        'is-active' => $activeRangeType === $key,
                    ])
                    @if ($activeRangeType === $key) aria-current="true" @endif
                >
                    <span>{{ $label }}</span>
                    @if ($key === 'custom')
                        <x-filament::icon icon="heroicon-m-chevron-down" class="reputalis-range-pill-chevron" />
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    @if ($isCustomRange)
        <div class="reputalis-range-custom">
            <label>
                <span>{{ __('client.dashboard.filters.from') }}</span>
                <input type="date" wire:model.live="date_from" />
            </label>
            <label>
                <span>{{ __('client.dashboard.filters.until') }}</span>
                <input type="date" wire:model.live="date_to" />
            </label>
        </div>
    @endif

    <div class="reputalis-internal-kpis">
        <article class="reputalis-kpi-card">
            <p class="reputalis-kpi-label">{{ __('client.dashboard.hero.avg_satisfaction') }}</p>
            <div class="reputalis-kpi-score">
                <span class="reputalis-kpi-value">{{ $summary['avg_score_precise'] ?? __('common.placeholders.empty') }}</span>
                <span class="reputalis-kpi-suffix">/5</span>
            </div>
            @if (! empty($summary['avg_delta_formatted']))
                <p @class([
                    'reputalis-kpi-delta',
                    'is-down' => $avgDeltaPositive === false,
                ])>
                    <span class="reputalis-kpi-delta-arrow" aria-hidden="true">{{ $avgDeltaPositive === false ? '↘' : '↗' }}</span>
                    {{ $summary['avg_delta_formatted'] }} {{ __('client.dashboard.hero.this_month') }}
                </p>
            @endif
        </article>

        <article class="reputalis-kpi-card">
            <p class="reputalis-kpi-label">{{ __('client.dashboard.hero.surveys') }}</p>
            <p class="reputalis-kpi-value">{{ number_format((int) ($summary['total_surveys'] ?? 0), 0, ',', '.') }}</p>
            <p class="reputalis-kpi-meta">
                <span>{{ trans_choice('client.dashboard.hero.responses_today', (int) ($summary['today_count'] ?? 0), ['count' => (int) ($summary['today_count'] ?? 0)]) }}</span>
                <span class="reputalis-kpi-check" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none">
                        <circle cx="10" cy="10" r="10" fill="#22c55e"/>
                        <path d="M6.2 10.3 8.6 12.7 13.8 7.4" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </p>
        </article>

        <article class="reputalis-kpi-card">
            <p class="reputalis-kpi-label">{{ __('client.dashboard.hero.positive_ratings') }}</p>
            <p class="reputalis-kpi-value">{{ $summary['satisfied_pct'] ?? __('common.placeholders.empty') }}</p>
            <p class="reputalis-kpi-hint">{{ $summary['positive_scores_hint'] ?? '' }}</p>
        </article>

        <article class="reputalis-kpi-card reputalis-kpi-card--distribution">
            <p class="reputalis-kpi-label">{{ __('client.dashboard.hero.score_distribution') }}</p>
            <div class="reputalis-score-dist">
                @foreach ($distributionBars as $bar)
                    <div class="reputalis-score-dist-col">
                        <span class="reputalis-score-dist-count">{{ $bar['count'] }}</span>
                        <span class="reputalis-score-dist-track">
                            <span
                                class="reputalis-score-dist-bar"
                                style="height: {{ $bar['height'] }}%; background: {{ $bar['color'] }};"
                            ></span>
                        </span>
                        <span class="reputalis-score-dist-label">{{ $bar['score'] }}</span>
                    </div>
                @endforeach
            </div>
        </article>
    </div>
</div>
