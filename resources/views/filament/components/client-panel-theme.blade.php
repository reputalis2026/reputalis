@if (\App\Support\ClientPanel::isActive())
    <style id="reputalis-client-panel-theme">
        html.reputalis-client-panel {
            --reputalis-sidebar: #06232b;
            --reputalis-sidebar-muted: #7f9aa3;
            --reputalis-sidebar-text: #d7e6ea;
            --reputalis-active: #2ad4dc;
            --reputalis-active-text: #043038;
            --reputalis-canvas: #e7f3ef;
            --reputalis-ink: #12353c;
        }

        html.reputalis-client-panel,
        html.reputalis-client-panel body,
        html.reputalis-client-panel .fi-body,
        html.reputalis-client-panel .fi-layout,
        html.reputalis-client-panel .fi-main-ctn {
            background-color: var(--reputalis-canvas) !important;
        }

        html.reputalis-client-panel .fi-main-sidebar.fi-sidebar,
        html.reputalis-client-panel .fi-sidebar,
        html.reputalis-client-panel .fi-sidebar-header {
            background-color: var(--reputalis-sidebar) !important;
            box-shadow: none !important;
            ring-width: 0 !important;
        }

        html.reputalis-client-panel .fi-sidebar-header {
            height: auto !important;
            min-height: 5.25rem;
            padding-top: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 0 !important;
        }

        html.reputalis-client-panel .fi-sidebar-header .fi-logo,
        html.reputalis-client-panel .fi-sidebar-header a:has(.fi-logo) {
            pointer-events: none;
            max-width: none;
        }

        html.reputalis-client-panel .reputalis-client-brand {
            display: flex;
            flex-direction: column;
            gap: .2rem;
            color: #fff;
        }

        html.reputalis-client-panel .reputalis-client-brand-name {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: .12em;
            line-height: 1.1;
            text-transform: uppercase;
        }

        html.reputalis-client-panel .reputalis-client-brand-tagline {
            font-size: .62rem;
            font-weight: 600;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--reputalis-sidebar-muted);
        }

        html.reputalis-client-panel .fi-sidebar-nav {
            padding-top: 1.25rem;
        }

        html.reputalis-client-panel .fi-sidebar-group-label {
            color: var(--reputalis-sidebar-muted) !important;
            font-size: .68rem !important;
            font-weight: 700 !important;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        html.reputalis-client-panel .fi-sidebar-group-collapse-button {
            display: none !important;
        }

        html.reputalis-client-panel .fi-sidebar-item-button {
            color: var(--reputalis-sidebar-text);
            border-radius: .85rem !important;
        }

        html.reputalis-client-panel .fi-sidebar-item-label {
            color: var(--reputalis-sidebar-text) !important;
        }

        html.reputalis-client-panel .fi-sidebar-item-button:hover,
        html.reputalis-client-panel .fi-sidebar-item-button:focus-visible {
            background-color: rgba(255, 255, 255, .06) !important;
        }

        html.reputalis-client-panel .fi-sidebar-item-icon {
            color: var(--reputalis-sidebar-muted) !important;
        }

        html.reputalis-client-panel .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
            background-color: var(--reputalis-active) !important;
            color: var(--reputalis-active-text) !important;
            font-weight: 700;
        }

        html.reputalis-client-panel .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
        html.reputalis-client-panel .fi-sidebar-item.fi-active .fi-sidebar-item-label {
            color: var(--reputalis-active-text) !important;
        }

        html.reputalis-client-panel .fi-topbar {
            background-color: var(--reputalis-canvas) !important;
            box-shadow: none !important;
            border-bottom: 0 !important;
        }

        @media (min-width: 1024px) {
            html.reputalis-client-panel .fi-topbar {
                display: none !important;
            }

            html.reputalis-client-panel .fi-main {
                padding-top: .75rem !important;
            }

            html.reputalis-client-panel .fi-page:has([data-dashboard-section="internal-reputation"]) {
                gap: 0 !important;
            }

            html.reputalis-client-panel .fi-page:has([data-dashboard-section="internal-reputation"]) .fi-page-content {
                padding-top: 0 !important;
            }
        }

        html.reputalis-client-panel .fi-topbar nav {
            background-color: var(--reputalis-canvas) !important;
            box-shadow: none !important;
        }

        html.reputalis-client-panel .fi-topbar .fi-logo,
        html.reputalis-client-panel .fi-topbar .fi-user-menu,
        html.reputalis-client-panel .fi-topbar nav > .ms-auto {
            display: none;
        }

        html.reputalis-client-panel .fi-topbar-open-sidebar-btn,
        html.reputalis-client-panel .fi-topbar-close-sidebar-btn {
            color: var(--reputalis-ink) !important;
        }

        html.reputalis-client-panel .fi-header-heading {
            color: var(--reputalis-ink);
            font-size: 1.55rem;
            font-weight: 700;
        }

        html.reputalis-client-panel .fi-page {
            color: var(--reputalis-ink);
        }

        html.reputalis-client-panel .reputalis-client-sidebar-footer {
            margin-top: auto;
            padding: 1rem 1.25rem 1.4rem;
            border-top: 1px solid rgba(255, 255, 255, .08);
            color: #fff;
        }

        html.reputalis-client-panel .reputalis-client-sidebar-account {
            display: flex;
            width: 100%;
            align-items: center;
            gap: .75rem;
            padding: .35rem .2rem;
            border-radius: .85rem;
            text-align: left;
            color: inherit;
        }

        html.reputalis-client-panel .reputalis-client-sidebar-account:hover {
            background-color: rgba(255, 255, 255, .06);
        }

        html.reputalis-client-panel .reputalis-client-sidebar-avatar,
        html.reputalis-client-panel .reputalis-client-sidebar-footer .fi-user-avatar,
        html.reputalis-client-panel .reputalis-client-sidebar-footer .fi-avatar {
            width: 2.5rem !important;
            height: 2.5rem !important;
            flex-shrink: 0;
        }

        html.reputalis-client-panel .reputalis-client-sidebar-account-text {
            display: flex;
            min-width: 0;
            flex-direction: column;
        }

        html.reputalis-client-panel .reputalis-client-sidebar-footer-name {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .92rem;
            font-weight: 700;
            line-height: 1.25;
        }

        html.reputalis-client-panel .reputalis-client-sidebar-footer-city {
            display: block;
            margin-top: .15rem;
            font-size: .75rem;
            color: var(--reputalis-sidebar-muted);
        }

        html.reputalis-client-panel .fi-page:has([data-dashboard-section="internal-reputation"]) .fi-header {
            display: none !important;
        }

        html.reputalis-client-panel .reputalis-internal-hero-wrap {
            margin-top: 0;
        }

        html.reputalis-client-panel .reputalis-internal-hero {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .65rem 1.25rem;
            margin-bottom: .9rem;
        }

        html.reputalis-client-panel .reputalis-internal-hero-copy h1 {
            margin: 0;
            color: var(--reputalis-ink);
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -.02em;
            line-height: 1.15;
        }

        html.reputalis-client-panel .reputalis-internal-hero-copy p {
            margin: .2rem 0 0;
            color: #7b9197;
            font-size: .88rem;
            font-weight: 500;
        }

        html.reputalis-client-panel .reputalis-range-pills {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .12rem;
            padding: .28rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .78);
            box-shadow: 0 1px 2px rgba(18, 53, 60, .06);
        }

        html.reputalis-client-panel .reputalis-range-pill {
            display: inline-flex;
            align-items: center;
            gap: .2rem;
            border: 0;
            background: transparent;
            color: #7b9197;
            cursor: pointer;
            font-size: .8rem;
            font-weight: 500;
            line-height: 1;
            padding: .48rem .9rem;
            border-radius: 999px;
            white-space: nowrap;
        }

        html.reputalis-client-panel .reputalis-range-pill:hover {
            color: var(--reputalis-ink);
        }

        html.reputalis-client-panel .reputalis-range-pill.is-active {
            background: #fff;
            color: var(--reputalis-ink);
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(18, 53, 60, .12);
        }

        html.reputalis-client-panel .reputalis-range-pill-chevron {
            width: .85rem;
            height: .85rem;
            color: currentColor;
        }

        html.reputalis-client-panel .reputalis-range-custom {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem 1rem;
            margin: -.4rem 0 1.15rem;
        }

        html.reputalis-client-panel .reputalis-range-custom label {
            display: flex;
            align-items: center;
            gap: .5rem;
            color: #7b9197;
            font-size: .78rem;
            font-weight: 600;
        }

        html.reputalis-client-panel .reputalis-range-custom input[type="date"] {
            border: 0;
            border-radius: .75rem;
            background: #fff;
            color: var(--reputalis-ink);
            font-size: .82rem;
            font-weight: 500;
            padding: .45rem .7rem;
            box-shadow: 0 1px 2px rgba(18, 53, 60, .06);
        }

        html.reputalis-client-panel .reputalis-internal-kpis {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1fr) minmax(13.5rem, 1.28fr);
            gap: 1rem;
        }

        html.reputalis-client-panel .reputalis-kpi-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: #fff;
            border-radius: 1.15rem;
            padding: .95rem .85rem .9rem;
            box-shadow: 0 10px 28px rgba(18, 53, 60, .05);
            min-width: 0;
            min-height: 9.25rem;
        }

        html.reputalis-client-panel .reputalis-kpi-card--distribution {
            padding: .85rem .75rem .7rem;
        }

        html.reputalis-client-panel .reputalis-kpi-label {
            margin: 0 0 .45rem;
            color: #8a9ea4;
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        html.reputalis-client-panel .reputalis-kpi-value {
            margin: 0;
            color: var(--reputalis-ink);
            font-size: 2.7rem;
            font-weight: 700;
            letter-spacing: -.04em;
            line-height: .95;
        }

        html.reputalis-client-panel .reputalis-kpi-score {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: .3rem;
        }

        html.reputalis-client-panel .reputalis-kpi-suffix {
            color: #b0c0c4;
            font-size: 1.05rem;
            font-weight: 600;
        }

        html.reputalis-client-panel .reputalis-kpi-delta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .28rem;
            margin: .7rem 0 0;
            padding: .38rem .8rem;
            border-radius: 999px;
            background: #e6f4ee;
            color: #0f6b53;
            font-size: .74rem;
            font-weight: 600;
            letter-spacing: 0;
            white-space: nowrap;
        }

        html.reputalis-client-panel .reputalis-kpi-delta.is-down {
            background: #fdecec;
            color: #dc2626;
        }

        html.reputalis-client-panel .reputalis-kpi-delta-arrow {
            font-size: .95rem;
            line-height: 1;
        }

        html.reputalis-client-panel .reputalis-kpi-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            margin: .5rem 0 0;
            color: #8a9ea4;
            font-size: .8rem;
            font-weight: 500;
        }

        html.reputalis-client-panel .reputalis-kpi-check {
            display: inline-flex;
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }

        html.reputalis-client-panel .reputalis-kpi-hint {
            margin: .5rem 0 0;
            color: #8a9ea4;
            font-size: .8rem;
            font-weight: 500;
        }

        html.reputalis-client-panel .reputalis-score-dist {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: .35rem;
            width: 100%;
            min-height: 6.2rem;
        }

        html.reputalis-client-panel .reputalis-score-dist-col {
            display: flex;
            flex: 1;
            flex-direction: column;
            align-items: center;
            min-width: 0;
        }

        html.reputalis-client-panel .reputalis-score-dist-count {
            color: #8a9ea4;
            font-size: .72rem;
            font-weight: 600;
            line-height: 1;
            margin-bottom: .35rem;
        }

        html.reputalis-client-panel .reputalis-score-dist-track {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            width: 100%;
            height: 4.6rem;
        }

        html.reputalis-client-panel .reputalis-score-dist-bar {
            display: block;
            width: 1.55rem;
            max-width: 70%;
            border-radius: .45rem;
            min-height: .35rem;
        }

        html.reputalis-client-panel .reputalis-score-dist-label {
            margin-top: .4rem;
            color: #8a9ea4;
            font-size: .78rem;
            font-weight: 600;
        }

        html.reputalis-client-panel .client-dashboard-insights-stack {
            flex-direction: column-reverse;
        }

        /* Crecimiento acumulado de encuestas (card del histórico, rol cliente) */
        html.reputalis-client-panel .client-dashboard-survey-history-card {
            border: 0;
            border-radius: 1.15rem;
            box-shadow: 0 10px 28px rgba(18, 53, 60, .05);
            overflow: visible;
        }

        html.reputalis-client-panel .client-dashboard-survey-history-header {
            border-bottom: 0;
            padding: 1.05rem 1.25rem .35rem;
            gap: .65rem;
        }

        @media (max-width: 720px) {
            html.reputalis-client-panel .client-dashboard-survey-history-header {
                flex-direction: column;
                align-items: flex-start;
                padding: .9rem 1rem .2rem;
            }

            html.reputalis-client-panel .client-dashboard-survey-history-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }

        html.reputalis-client-panel .client-dashboard-survey-history-title {
            color: var(--reputalis-ink);
            font-size: 1.02rem;
            font-weight: 700;
            letter-spacing: -.01em;
            gap: .45rem;
        }

        html.reputalis-client-panel .client-dashboard-survey-history-icon {
            width: 1.05rem;
            height: 1.05rem;
            background: rgba(138, 158, 164, .16);
            color: #8a9ea4;
            cursor: help;
        }

        html.reputalis-client-panel .client-dashboard-survey-history-icon svg {
            width: .8rem;
            height: .8rem;
        }

        html.reputalis-client-panel .client-dashboard-survey-history-actions {
            gap: .4rem;
            color: #8a9ea4;
            font-size: .68rem;
        }

        html.reputalis-client-panel .reputalis-growth-chip {
            display: inline-flex;
            align-items: center;
            margin-right: .35rem;
            padding: .38rem .8rem;
            border-radius: 999px;
            background: #e6f4ee;
            color: #0f6b53;
            font-size: .74rem;
            font-weight: 600;
            letter-spacing: 0;
            white-space: nowrap;
        }

        html.reputalis-client-panel .client-dashboard-survey-history-pill {
            border-radius: 999px;
            border: 1px solid rgba(18, 53, 60, .12);
            background: #fff;
            color: #7b9197;
            font-size: .7rem;
            font-weight: 600;
            padding: .3rem .65rem;
        }

        html.reputalis-client-panel .client-dashboard-survey-history-pill.is-active {
            border-color: transparent;
            background: #e6f4ee;
            color: #0f6b53;
        }

        html.reputalis-client-panel .client-dashboard-insights-row > .client-dashboard-survey-history-card:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-body {
            padding: .2rem 1rem .7rem;
        }

        html.reputalis-client-panel .client-dashboard-insights-row > .client-dashboard-survey-history-card.is-hours-grouping:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-body {
            padding-bottom: 1.35rem;
        }

        html.reputalis-client-panel .client-dashboard-insights-row > .client-dashboard-survey-history-card.is-hours-grouping:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-chart {
            height: 17.75rem;
        }

        html.reputalis-client-panel .client-dashboard-insights-row > .client-dashboard-survey-history-card.is-month-axis:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-body {
            padding-bottom: 1.5rem;
        }

        html.reputalis-client-panel .client-dashboard-insights-row > .client-dashboard-survey-history-card.is-month-axis:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-chart {
            height: 18.5rem;
        }

        @media (max-width: 768px) {
            html.reputalis-client-panel .client-dashboard-insights-row > .client-dashboard-survey-history-card.is-day-axis:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-body {
                padding-bottom: 1.35rem;
            }

            html.reputalis-client-panel .client-dashboard-insights-row > .client-dashboard-survey-history-card.is-month-axis:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-body {
                padding-bottom: 1.75rem;
            }

            html.reputalis-client-panel .client-dashboard-insights-row > .client-dashboard-survey-history-card.is-month-axis:not(.client-dashboard-score-trend-card) .client-dashboard-survey-history-chart {
                height: 19.5rem;
            }
        }

        html.reputalis-client-panel .client-dashboard-survey-history-card {
            overflow: visible;
        }

        html.reputalis-client-panel .client-dashboard-survey-history-chart,
        html.reputalis-client-panel [data-dashboard-chart="survey-history"],
        html.reputalis-client-panel [data-dashboard-chart="survey-history"] .apexcharts-canvas,
        html.reputalis-client-panel [data-dashboard-chart="survey-history"] .apexcharts-svg,
        html.reputalis-client-panel [data-dashboard-chart="survey-history"] .apexcharts-inner {
            overflow: visible;
        }

        html.reputalis-client-panel [data-dashboard-chart="survey-history"] .apexcharts-xaxis-label {
            opacity: 1 !important;
        }

        html.reputalis-client-panel [data-dashboard-chart="survey-history"] .apexcharts-area-series .apexcharts-area,
        html.reputalis-client-panel [data-dashboard-chart="survey-history"] .apexcharts-line,
        html.reputalis-client-panel [data-dashboard-chart="score-trend"] .apexcharts-area-series .apexcharts-area,
        html.reputalis-client-panel [data-dashboard-chart="score-trend"] .apexcharts-line,
        html.reputalis-client-panel [data-dashboard-chart="score-trend"] .apexcharts-series path {
            filter: none;
        }

        html.reputalis-client-panel .client-dashboard-score-trend-card {
            overflow: visible;
            min-height: 0;
        }

        html.reputalis-client-panel .client-dashboard-insights-row > .client-dashboard-score-trend-card .client-dashboard-survey-history-body {
            padding: .2rem 1rem .9rem;
        }

        html.reputalis-client-panel .client-dashboard-insights-row > .client-dashboard-score-trend-card .client-dashboard-survey-history-chart {
            height: 16.5rem;
        }

        html.reputalis-client-panel [data-dashboard-chart="score-trend"],
        html.reputalis-client-panel [data-dashboard-chart="score-trend"] .apexcharts-canvas,
        html.reputalis-client-panel [data-dashboard-chart="score-trend"] .apexcharts-svg,
        html.reputalis-client-panel [data-dashboard-chart="score-trend"] .apexcharts-inner {
            overflow: visible;
        }

        html.reputalis-client-panel [data-dashboard-chart="score-trend"] .apexcharts-xaxis-label,
        html.reputalis-client-panel [data-dashboard-chart="employee-trend"] .apexcharts-xaxis-label,
        html.reputalis-client-panel [data-dashboard-chart="improvement-detail"] .apexcharts-xaxis-label {
            opacity: 1 !important;
        }

        html.reputalis-client-panel [data-dashboard-chart="employee-trend"] .apexcharts-area-series .apexcharts-area,
        html.reputalis-client-panel [data-dashboard-chart="employee-trend"] .apexcharts-line,
        html.reputalis-client-panel [data-dashboard-chart="improvement-detail"] .apexcharts-area-series .apexcharts-area,
        html.reputalis-client-panel [data-dashboard-chart="improvement-detail"] .apexcharts-line {
            filter: none;
        }

        @media (max-width: 768px) {
            html.reputalis-client-panel .client-dashboard-insights-row > .client-dashboard-score-trend-card .client-dashboard-survey-history-chart {
                height: 18rem;
            }
        }

        html.reputalis-client-panel .client-dashboard-employee-detail-filters,
        html.reputalis-client-panel .client-dashboard-improvement-detail-filters {
            display: inline-flex;
            flex-wrap: wrap;
            gap: .12rem;
            margin: 0 1.25rem .95rem;
            padding: .28rem;
            border-radius: 999px;
            background: #f3f7f6;
            box-shadow: 0 1px 2px rgba(18, 53, 60, .06);
        }

        html.reputalis-client-panel .client-dashboard-employee-detail-filter-pill,
        html.reputalis-client-panel .client-dashboard-improvement-detail-filter-pill {
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #7b9197;
            font-size: .8rem;
            font-weight: 500;
            padding: .48rem .9rem;
        }

        html.reputalis-client-panel .client-dashboard-employee-detail-filter-pill.is-active,
        html.reputalis-client-panel .client-dashboard-improvement-detail-filter-pill.is-active {
            background: #e6f4ee;
            color: #0f6b53;
            box-shadow: none;
        }

        html.reputalis-client-panel .client-dashboard-improvement-ranking-card {
            border: 0;
            border-radius: 1.15rem;
            box-shadow: 0 10px 28px rgba(18, 53, 60, .05);
            overflow: visible;
        }

        html.reputalis-client-panel .client-dashboard-improvement-ranking-header {
            display: block;
            border-bottom: 0;
            padding: 1.15rem 1.25rem .35rem;
        }

        html.reputalis-client-panel .client-dashboard-improvement-ranking-header h3 {
            color: var(--reputalis-ink);
            font-size: 1.02rem;
            font-weight: 700;
            letter-spacing: -.01em;
        }

        html.reputalis-client-panel .reputalis-improve-subtitle {
            margin: .28rem 0 0;
            color: #8a9ea4;
            font-size: .78rem;
            font-weight: 500;
            line-height: 1.35;
        }

        html.reputalis-client-panel .client-dashboard-improvement-ranking-body {
            background: #fff;
            padding: .35rem 1.15rem 1.15rem;
        }

        html.reputalis-client-panel .client-dashboard-improvement-ranking-scroll {
            max-height: none;
            overflow: visible;
            padding: .15rem 0 .1rem .9rem;
        }

        html.reputalis-client-panel .client-dashboard-improvement-row.reputalis-improve-row {
            display: flex;
            flex-direction: column;
            gap: .4rem;
            width: 100%;
            margin: 0 0 .35rem;
            padding: .7rem .8rem .75rem 1rem;
            overflow: visible;
            border: 1px solid transparent;
            border-radius: .85rem;
            background: transparent;
            box-shadow: none;
            transform: none;
        }

        html.reputalis-client-panel .client-dashboard-improvement-row.reputalis-improve-row::before {
            display: none;
        }

        html.reputalis-client-panel .client-dashboard-improvement-row.reputalis-improve-row:hover,
        html.reputalis-client-panel .client-dashboard-improvement-row.reputalis-improve-row:focus-visible {
            border-color: rgba(18, 53, 60, .14);
            background: #f7fbf9;
            box-shadow: 0 0 0 1px rgba(18, 53, 60, .04);
            transform: none;
        }

        html.reputalis-client-panel .client-dashboard-improvement-row.reputalis-improve-row .client-dashboard-improvement-info-tab {
            width: 1.05rem;
            height: 2.35rem;
            min-height: 2.35rem;
            max-height: 2.35rem;
            border-radius: .35rem 0 0 .35rem;
            background: #0f6b53;
            box-shadow: -2px 0 8px rgba(15, 107, 83, .18);
            transform: translate(calc(-100% - .55rem), -50%);
        }

        html.reputalis-client-panel .client-dashboard-improvement-row.reputalis-improve-row:hover .client-dashboard-improvement-info-tab,
        html.reputalis-client-panel .client-dashboard-improvement-row.reputalis-improve-row:focus-visible .client-dashboard-improvement-info-tab {
            opacity: 1;
            transform: translate(calc(-100% - .15rem), -50%);
        }

        html.reputalis-client-panel .reputalis-improve-copy {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: .75rem;
        }

        html.reputalis-client-panel .reputalis-improve-label {
            color: var(--reputalis-ink);
            font-size: .9rem;
            font-weight: 600;
            line-height: 1.3;
        }

        html.reputalis-client-panel .reputalis-improve-pct {
            flex: 0 0 auto;
            color: #8a9ea4;
            font-size: .88rem;
            font-weight: 700;
        }

        html.reputalis-client-panel .reputalis-improve-track {
            width: 100%;
            height: .55rem;
            overflow: hidden;
            border-radius: 999px;
            background: #eef3f4;
        }

        html.reputalis-client-panel .reputalis-improve-bar {
            display: block;
            height: 100%;
            min-width: 0;
            border-radius: inherit;
        }

        html.reputalis-client-panel .client-dashboard-improvement-row.reputalis-improve-row.is-inactive {
            border-style: solid;
            background: #f6f8f8;
            filter: grayscale(.25);
        }

        @media (max-width: 700px) {
            html.reputalis-client-panel .client-dashboard-improvement-row.reputalis-improve-row {
                grid-template-columns: unset;
            }

            html.reputalis-client-panel .client-dashboard-improvement-ranking-scroll {
                padding-left: .85rem;
            }
        }

        @media (max-width: 1180px) {
            html.reputalis-client-panel .reputalis-internal-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            html.reputalis-client-panel .reputalis-internal-hero-copy h1 {
                font-size: 1.35rem;
            }

            html.reputalis-client-panel .reputalis-range-pills {
                width: 100%;
                border-radius: 1.15rem;
            }

            html.reputalis-client-panel .reputalis-internal-kpis {
                grid-template-columns: minmax(0, 1fr);
            }

            html.reputalis-client-panel .reputalis-kpi-value {
                font-size: 2.35rem;
            }
        }
    </style>
    <script>
        window.applyReputalisClientPanel = function () {
            document.documentElement.classList.add('reputalis-client-panel');
            if (document.body) {
                document.body.classList.add('reputalis-client-panel');
            }
        };

        window.applyReputalisClientPanel();

        if (! window.reputalisClientPanelBound) {
            window.reputalisClientPanelBound = true;
            document.addEventListener('livewire:navigated', window.applyReputalisClientPanel);
        }
    </script>
@endif
