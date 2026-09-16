<x-filament-panels::page>
    <style>
        .additional-tools-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        @media (min-width: 640px) {
            .additional-tools-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .additional-tools-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .additional-tools-card {
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

        .additional-tools-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, .08);
        }

        .additional-tools-card-icon {
            display: flex;
            width: 2.75rem;
            height: 2.75rem;
            flex: 0 0 2.75rem;
            align-items: center;
            justify-content: center;
            border-radius: .7rem;
        }

        .additional-tools-card-body {
            min-width: 0;
            flex: 1 1 auto;
        }

        .additional-tools-card-title {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .additional-tools-card-desc {
            margin-top: .3rem;
            font-size: .875rem;
            line-height: 1.4;
            opacity: .85;
        }

        .additional-tools-card--sectors {
            background: #d1fae5;
            border-color: #6ee7b7;
            color: #065f46;
        }

        .additional-tools-card--sectors .additional-tools-card-icon {
            background: #059669;
            color: #ecfdf5;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, .18);
        }

        .additional-tools-card--sectors:hover {
            border-color: #34d399;
        }

        .additional-tools-card--images {
            background: #ffedd5;
            border-color: #fdba74;
            color: #9a3412;
        }

        .additional-tools-card--images .additional-tools-card-icon {
            background: #ea580c;
            color: #fff7ed;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, .18);
        }

        .additional-tools-card--images:hover {
            border-color: #fb923c;
        }

        .dark .additional-tools-card--sectors {
            background: rgba(4, 120, 87, .42);
            border-color: rgba(52, 211, 153, .35);
            color: #a7f3d0;
        }

        .dark .additional-tools-card--sectors .additional-tools-card-icon {
            background: #10b981;
            color: #022c22;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, .2);
        }

        .dark .additional-tools-card--images {
            background: rgba(154, 52, 18, .42);
            border-color: rgba(251, 146, 60, .35);
            color: #fdba74;
        }

        .dark .additional-tools-card--images .additional-tools-card-icon {
            background: #f97316;
            color: #431407;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, .2);
        }
    </style>

    <div class="additional-tools-grid">
        @if ($this->canManageSectors())
            <a
                href="{{ \App\Filament\Resources\SectorResource::getUrl('index') }}"
                class="additional-tools-card additional-tools-card--sectors"
            >
                <div class="additional-tools-card-icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-6 w-6" />
                </div>
                <div class="additional-tools-card-body">
                    <h3 class="additional-tools-card-title">
                        {{ __('panel.additional_tools.cards.sectors.title') }}
                    </h3>
                    <p class="additional-tools-card-desc">
                        {{ __('panel.additional_tools.cards.sectors.description') }}
                    </p>
                </div>
            </a>
        @endif

        <a
            href="{{ \App\Filament\Pages\ClientImagesGallery::getUrl() }}"
            class="additional-tools-card additional-tools-card--images"
        >
            <div class="additional-tools-card-icon" aria-hidden="true">
                <x-filament::icon icon="heroicon-o-photo" class="h-6 w-6" />
            </div>
            <div class="additional-tools-card-body">
                <h3 class="additional-tools-card-title">
                    {{ __('panel.additional_tools.cards.client_images.title') }}
                </h3>
                <p class="additional-tools-card-desc">
                    {{ __('panel.additional_tools.cards.client_images.description') }}
                </p>
            </div>
        </a>
    </div>
</x-filament-panels::page>
