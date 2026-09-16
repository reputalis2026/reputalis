<x-filament-panels::page>
    <style>
        .client-hub-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        @media (min-width: 640px) {
            .client-hub-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .client-hub-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .client-hub-card {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            border-radius: .9rem;
            border: 1px solid transparent;
            padding: 1.1rem 1.15rem;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        }

        .client-hub-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, .08);
        }

        .client-hub-card-icon {
            display: flex;
            width: 2.75rem;
            height: 2.75rem;
            flex: 0 0 2.75rem;
            align-items: center;
            justify-content: center;
            border-radius: .7rem;
        }

        .client-hub-card-body {
            min-width: 0;
            flex: 1 1 auto;
        }

        .client-hub-card-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .client-hub-card-desc {
            margin-top: .3rem;
            font-size: .875rem;
            line-height: 1.4;
            opacity: .85;
        }

        .client-hub-card--dashboard {
            background: #cffafe;
            border-color: #67e8f9;
            color: #155e75;
        }

        .client-hub-card--dashboard .client-hub-card-icon {
            background: #0891b2;
            color: #ecfeff;
            box-shadow: 0 0 0 3px rgba(8, 145, 178, .18);
        }

        .client-hub-card--dashboard:hover {
            border-color: #22d3ee;
        }

        .client-hub-card--profile {
            background: #d1fae5;
            border-color: #6ee7b7;
            color: #065f46;
        }

        .client-hub-card--profile .client-hub-card-icon {
            background: #059669;
            color: #ecfdf5;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, .18);
        }

        .client-hub-card--profile:hover {
            border-color: #34d399;
        }

        .client-hub-card--survey {
            background: #ffedd5;
            border-color: #fdba74;
            color: #9a3412;
        }

        .client-hub-card--survey .client-hub-card-icon {
            background: #ea580c;
            color: #fff7ed;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, .18);
        }

        .client-hub-card--survey:hover {
            border-color: #fb923c;
        }

        .client-hub-card--employees {
            background: #ede9fe;
            border-color: #c4b5fd;
            color: #5b21b6;
        }

        .client-hub-card--employees .client-hub-card-icon {
            background: #7c3aed;
            color: #f5f3ff;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, .18);
        }

        .client-hub-card--employees:hover {
            border-color: #a78bfa;
        }

        .client-hub-card--calls {
            background: #dbeafe;
            border-color: #93c5fd;
            color: #1e40af;
        }

        .client-hub-card--calls .client-hub-card-icon {
            background: #2563eb;
            color: #eff6ff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .18);
        }

        .client-hub-card--calls:hover {
            border-color: #60a5fa;
        }

        .dark .client-hub-card--dashboard {
            background: rgba(8, 145, 178, .42);
            border-color: rgba(103, 232, 249, .35);
            color: #a5f3fc;
        }

        .dark .client-hub-card--profile {
            background: rgba(4, 120, 87, .42);
            border-color: rgba(52, 211, 153, .35);
            color: #a7f3d0;
        }

        .dark .client-hub-card--survey {
            background: rgba(154, 52, 18, .42);
            border-color: rgba(251, 146, 60, .35);
            color: #fdba74;
        }

        .dark .client-hub-card--employees {
            background: rgba(91, 33, 182, .42);
            border-color: rgba(167, 139, 250, .35);
            color: #ddd6fe;
        }

        .dark .client-hub-card--calls {
            background: rgba(37, 99, 235, .42);
            border-color: rgba(147, 197, 253, .35);
            color: #bfdbfe;
        }
    </style>

    <div class="client-hub-grid">
        <a
            href="{{ \App\Filament\Resources\ClientResource::getUrl('dashboard', ['record' => $this->getRecord()]) }}"
            class="client-hub-card client-hub-card--dashboard"
        >
            <div class="client-hub-card-icon" aria-hidden="true">
                <x-filament::icon icon="heroicon-o-chart-bar-square" class="h-6 w-6" />
            </div>
            <div class="client-hub-card-body">
                <h3 class="client-hub-card-title">{{ __('client.hub.cards.dashboard.title') }}</h3>
                <p class="client-hub-card-desc">{{ __('client.hub.cards.dashboard.description') }}</p>
            </div>
        </a>

        <a
            href="{{ \App\Filament\Resources\ClientResource::getUrl('view', ['record' => $this->getRecord()]) }}"
            class="client-hub-card client-hub-card--profile"
        >
            <div class="client-hub-card-icon" aria-hidden="true">
                <x-filament::icon icon="heroicon-o-identification" class="h-6 w-6" />
            </div>
            <div class="client-hub-card-body">
                <h3 class="client-hub-card-title">{{ __('client.hub.cards.profile.title') }}</h3>
                <p class="client-hub-card-desc">{{ __('client.hub.cards.profile.description') }}</p>
            </div>
        </a>

        <a
            href="{{ \App\Filament\Resources\ClientResource::getUrl('puntos-de-mejora', ['record' => $this->getRecord()]) }}"
            class="client-hub-card client-hub-card--survey"
        >
            <div class="client-hub-card-icon" aria-hidden="true">
                <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-6 w-6" />
            </div>
            <div class="client-hub-card-body">
                <h3 class="client-hub-card-title">{{ __('client.hub.cards.survey.title') }}</h3>
                <p class="client-hub-card-desc">{{ __('client.hub.cards.survey.description') }}</p>
            </div>
        </a>

        <a
            href="{{ \App\Filament\Resources\ClientResource::getUrl('empleados', ['record' => $this->getRecord()]) }}"
            class="client-hub-card client-hub-card--employees"
        >
            <div class="client-hub-card-icon" aria-hidden="true">
                <x-filament::icon icon="heroicon-o-user-group" class="h-6 w-6" />
            </div>
            <div class="client-hub-card-body">
                <h3 class="client-hub-card-title">{{ __('client.hub.cards.employees.title') }}</h3>
                <p class="client-hub-card-desc">{{ __('client.hub.cards.employees.description') }}</p>
            </div>
        </a>

        @if ($this->canSeeCalls())
            <a
                href="{{ \App\Filament\Resources\ClientResource::getUrl('llamadas', ['record' => $this->getRecord()]) }}"
                class="client-hub-card client-hub-card--calls"
            >
                <div class="client-hub-card-icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-phone-arrow-up-right" class="h-6 w-6" />
                </div>
                <div class="client-hub-card-body">
                    <h3 class="client-hub-card-title">{{ __('client.hub.cards.calls.title') }}</h3>
                    <p class="client-hub-card-desc">{{ __('client.hub.cards.calls.description') }}</p>
                </div>
            </a>
        @endif
    </div>
</x-filament-panels::page>
