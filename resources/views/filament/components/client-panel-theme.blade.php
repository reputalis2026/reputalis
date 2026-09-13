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
            margin: .45rem 0 0;
            color: #16a34a;
            font-size: .8rem;
            font-weight: 600;
        }

        html.reputalis-client-panel .reputalis-kpi-delta.is-down {
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
