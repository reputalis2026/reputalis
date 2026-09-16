@php
    $hero = $hero ?? [];
    $distribution = $hero['distribution'] ?? [];
@endphp

<div
    class="reputalis-internal-hero-wrap reputalis-external-hero-wrap"
    data-dashboard-section="external-reputation"
    wire:key="external-hero-{{ $hero['google_rating_formatted'] ?? 'empty' }}-{{ $hero['total_reviews'] ?? 0 }}"
>
    <div class="reputalis-internal-hero">
        <div class="reputalis-internal-hero-copy">
            <h1>{{ __('client.dashboard.external.heading') }}</h1>
            <p>{{ __('client.dashboard.external.subheading') }}</p>
        </div>
    </div>

    <div class="reputalis-external-kpis">
        <article class="reputalis-kpi-card">
            <p class="reputalis-kpi-label">{{ __('client.external_reputation.hero.google_rating') }}</p>
            <div class="reputalis-kpi-score">
                <span class="reputalis-kpi-value">{{ $hero['google_rating_formatted'] ?? __('common.placeholders.empty') }}</span>
                @if (($hero['has_snapshot'] ?? false) && ($hero['google_rating_formatted'] ?? '') !== __('common.placeholders.empty'))
                    <span class="reputalis-kpi-suffix">/5</span>
                @endif
            </div>
            <p class="reputalis-kpi-hint">{{ __('client.external_reputation.hero.google_source') }}</p>
        </article>

        <article class="reputalis-kpi-card">
            <p class="reputalis-kpi-label">{{ __('client.external_reputation.hero.reviews_total') }}</p>
            <p class="reputalis-kpi-value">{{ number_format((int) ($hero['total_reviews'] ?? 0), 0, ',', '.') }}</p>
            <p class="reputalis-kpi-hint">{{ __('client.external_reputation.hero.reviews_total_hint') }}</p>
        </article>

        <article class="reputalis-kpi-card">
            <p class="reputalis-kpi-label">{{ __('client.external_reputation.hero.positive_ratings') }}</p>
            <p class="reputalis-kpi-value">{{ $hero['positive_pct_formatted'] ?? __('common.placeholders.empty') }}</p>
            <p class="reputalis-kpi-hint">{{ __('client.external_reputation.hero.positive_hint') }}</p>
        </article>

        <article class="reputalis-kpi-card reputalis-kpi-card--distribution">
            <p class="reputalis-kpi-label">{{ __('client.external_reputation.hero.score_distribution') }}</p>
            <div class="reputalis-score-dist">
                @foreach ($distribution as $bar)
                    <div class="reputalis-score-dist-col">
                        <span class="reputalis-score-dist-count">{{ $bar['count'] }}</span>
                        <span class="reputalis-score-dist-track">
                            <span
                                class="reputalis-score-dist-bar"
                                style="height: {{ $bar['height'] }}%; background: {{ $bar['color'] }};"
                            ></span>
                        </span>
                        <span class="reputalis-score-dist-label">{{ $bar['score'] }}★</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="reputalis-kpi-card">
            <p class="reputalis-kpi-label">{{ __('client.external_reputation.hero.real_rating') }}</p>
            <div class="reputalis-kpi-score">
                <span class="reputalis-kpi-value">{{ $hero['real_rating_formatted'] ?? __('common.placeholders.empty') }}</span>
                @if (($hero['has_snapshot'] ?? false) && ($hero['real_rating_formatted'] ?? '') !== __('common.placeholders.empty'))
                    <span class="reputalis-kpi-suffix">/5</span>
                @endif
            </div>
            <p class="reputalis-kpi-hint">
                {{ __('client.external_reputation.hero.real_hint', ['count' => (int) ($hero['total_reviews'] ?? 0)]) }}
            </p>
        </article>

        <article class="reputalis-kpi-card reputalis-kpi-card--target">
            <p class="reputalis-kpi-label">{{ __('client.external_reputation.hero.target_rating') }}</p>
            <p class="reputalis-kpi-value reputalis-kpi-value--accent">{{ $hero['target_formatted'] ?? __('common.placeholders.empty') }}</p>
            <p class="reputalis-kpi-hint reputalis-kpi-hint--accent">{{ __('client.external_reputation.hero.target_hint') }}</p>
        </article>

        <article class="reputalis-kpi-card">
            <p class="reputalis-kpi-label">{{ __('client.external_reputation.hero.stars_needed') }}</p>
            <p class="reputalis-kpi-value">{{ $hero['stars_needed_formatted'] ?? __('common.placeholders.empty') }}</p>
            <p class="reputalis-kpi-hint">{{ __('client.external_reputation.hero.stars_needed_hint') }}</p>
        </article>

        <article class="reputalis-kpi-card reputalis-kpi-card--progress">
            <p class="reputalis-kpi-label">{{ __('client.external_reputation.hero.progress') }}</p>
            <div class="reputalis-progress-gauge" aria-label="{{ ($hero['progress_pct'] ?? 0) }}%">
                <svg viewBox="0 0 140 95" class="reputalis-progress-gauge-svg">
                    <path d="M15,75 A55,55 0 0,1 125,75" fill="none" stroke="#d7e6ea" stroke-width="12" stroke-linecap="round"/>
                    @if (($hero['progress_pct'] ?? 0) > 0)
                        <path
                            d="M15,75 A55,55 0 0,1 125,75"
                            fill="none"
                            stroke="#2eb5d6"
                            stroke-width="12"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $hero['arc_progress'] ?? 0 }} {{ $hero['arc_total'] ?? 173 }}"
                        />
                    @endif
                    <circle
                        cx="{{ $hero['star_x'] ?? 15 }}"
                        cy="{{ $hero['star_y'] ?? 75 }}"
                        r="7"
                        fill="#ffffff"
                        stroke="#2eb5d6"
                        stroke-width="3"
                    />
                    <text x="70" y="68" text-anchor="middle" class="reputalis-progress-gauge-value">{{ $hero['progress_pct'] ?? 0 }}%</text>
                    <text x="12" y="92" text-anchor="middle" class="reputalis-progress-gauge-end">{{ $hero['progress_from'] ?? '' }}</text>
                    <text x="128" y="92" text-anchor="middle" class="reputalis-progress-gauge-end">{{ $hero['progress_to'] ?? '' }}</text>
                </svg>
            </div>
        </article>
    </div>
</div>
