@extends('pulse.layout')

@section('content')
    <main class="home">
        <nav class="home-nav" aria-label="Accesos">
            <a href="{{ $links['soporte'] }}">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3"/><path d="M5 19c1.5-3 4-4.5 7-4.5S17.5 16 19 19"/></svg>
                Soporte
            </a>
            <a href="{{ $links['alertas'] }}">
                @if($home['alerts'] > 0)<span class="badge">{{ $home['alerts'] }}</span>@endif
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 4l8 14H4L12 4z"/><path d="M12 10v4"/><path d="M12 16.5h.01"/></svg>
                Alertas
            </a>
            <a href="{{ $links['notificaciones'] }}">
                @if($home['notifications'] > 0)<span class="badge badge-teal">{{ $home['notifications'] }}</span>@endif
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 9a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9"/><path d="M10 20a2 2 0 0 0 4 0"/></svg>
                Notificaciones
            </a>
        </nav>

        <a class="card home-card home-internal" href="{{ $links['interna'] }}">
            <h2>Reputación interna <span class="chev">›</span></h2>
            @if($home['internal']['empty'])
                @include('pulse.partials.empty', ['message' => 'Sin encuestas todavía'])
            @else
                <div class="kpis">
                    <div class="kpi">
                        <span>Nota acumulada</span>
                        <strong>{{ \App\Support\Pulse\PulseFormat::decimal($home['internal']['average'], 1) }}</strong>
                    </div>
                    <div class="kpi">
                        <span>Encuestas</span>
                        <strong>{{ \App\Support\Pulse\PulseFormat::integer($home['internal']['total']) }}</strong>
                    </div>
                </div>
                <div class="split home-internal-foot home-foot">
                    <div class="today-note">
                        @if($home['today']['empty'])
                            <span class="muted">Hoy</span>
                            <b>0</b>
                            <span class="muted">Sin encuestas</span>
                        @else
                            <span class="muted">Hoy</span>
                            <b>{{ \App\Support\Pulse\PulseFormat::decimal($home['today']['average'], 1) }}</b>
                            <span class="muted">{{ \App\Support\Pulse\PulseFormat::integer($home['today']['total']) }} encuestas</span>
                        @endif
                    </div>
                    <div>
                        <p class="muted">Distribución</p>
                        @include('pulse.partials.distribution', ['bars' => $home['internal']['distribution'], 'tracked' => true, 'hideScale' => true])
                    </div>
                </div>
            @endif
        </a>

        <a class="card home-card home-external" href="{{ $links['externa'] }}">
            <h2>Reputación externa <span class="chev">›</span></h2>
            <p class="sub">Google Maps</p>
            @if($home['external'] === null)
                @include('pulse.partials.empty', ['message' => 'Sin datos de Google', 'retry' => $externalError !== null])
            @else
                <div class="kpis">
                    <div class="kpi">
                        <span>Nota Google</span>
                        <strong>{{ $home['external']['google_label'] ?? '—' }}</strong>
                    </div>
                    <div class="kpi">
                        <span>Reseñas</span>
                        <strong>{{ \App\Support\Pulse\PulseFormat::integer($home['external']['reviews']) }}</strong>
                    </div>
                </div>
                <div class="home-external-mid">
                    <div>
                        <span>Nota real</span>
                        <strong>{{ $home['external']['real_label'] ?? '—' }}</strong>
                    </div>
                    <div class="is-goal">
                        <span>Objetivo</span>
                        <strong>{{ $home['external']['target_label'] ?? '—' }}</strong>
                    </div>
                    <div>
                        <span>Reseñas de 5★<br>necesarias</span>
                        <strong>{{ $home['external']['needed'] !== null ? \App\Support\Pulse\PulseFormat::integer((int) $home['external']['needed']) : '—' }}</strong>
                    </div>
                </div>
                <div class="split home-external-foot home-foot">
                    <div class="today-note">
                        <span class="muted">Hoy</span>
                        <b>{{ $home['today']['empty'] ? '0' : \App\Support\Pulse\PulseFormat::decimal($home['today']['average'], 1) }}</b>
                        <span class="muted">{{ \App\Support\Pulse\PulseFormat::integer((int) ($home['external']['reviews_today'] ?? 0)) }} reseñas</span>
                    </div>
                    <div>
                        <p class="muted">Distribución</p>
                        @include('pulse.partials.distribution', ['bars' => $home['external']['distribution'], 'tracked' => true, 'showStars' => true])
                    </div>
                </div>
            @endif
        </a>

        <a class="card home-card home-sector" href="{{ $links['sector'] }}">
            <h2>Comparativa sector <span class="chev">›</span></h2>
            <p class="sub">Competidores de tu código postal</p>
            <div class="sector-home">
                <div class="sector-kpi">
                    <span>Tu posición</span>
                    <strong><b>{{ \App\Support\Pulse\PulseFormat::ordinal($sector['position']) }}</b> / {{ $sector['analyzed'] }}</strong>
                </div>
                <p class="sector-banner">
                    Con {{ \App\Support\Pulse\PulseFormat::integer($sector['projection_stars']) }} reseñas de 5★ puedes alcanzar la <b>{{ \App\Support\Pulse\PulseFormat::ordinal($sector['projection_position'], true) }}</b> posición
                </p>
            </div>
        </a>

        <div class="home-actions">
            <a class="card" href="{{ $links['equipo'] }}">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="9" r="2.5"/><circle cx="16" cy="9" r="2.5"/><path d="M3 18c1-2.5 2.8-3.5 5-3.5s4 1 5 3.5M13 18c.6-1.6 1.8-2.6 3.5-2.6 1.6 0 3 .8 3.8 2.6"/></svg>
                Equipo <span class="chev">›</span>
            </a>
            <a class="card" href="{{ $links['mejoras'] }}">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V10M10 19V5M16 19v-7M22 19H2"/></svg>
                Puntos de mejora <span class="chev">›</span>
            </a>
        </div>
    </main>
@endsection
