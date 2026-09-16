<div class="reputalis-theme-flags" hidden data-staff="{{ (auth()->user()?->isSuperAdmin() || auth()->user()?->isDistributor()) ? '1' : '0' }}" data-preview="{{ \App\Support\ClientPanel::showsStaffReturnBar() ? '1' : '0' }}" data-fullscreen="{{ \App\Support\ClientPanel::hidesStaffSidebar() ? '1' : '0' }}"></div>
@if (\App\Support\ClientPanel::showsStaffReturnBar())
    @php
        $client = \App\Support\ClientPanel::staffReturnClient();
        $exitUrl = \App\Support\ClientPanel::previewExitUrl($client);
    @endphp
    @if ($client && filled($exitUrl))
        <div class="reputalis-admin-preview-bar" role="status">
            <span class="reputalis-admin-preview-bar-text">
                {{ __('client.preview.viewing', ['client' => $client->namecommercial]) }}
            </span>
            <a href="{{ $exitUrl }}" class="reputalis-admin-preview-bar-button" wire:navigate>
                <x-filament::icon icon="heroicon-m-arrow-left" class="h-4 w-4" />
                <span>{{ __('client.preview.back') }}</span>
            </a>
        </div>
        <style>
            .reputalis-admin-preview-bar {
                position: fixed;
                left: 50%;
                bottom: 1.25rem;
                z-index: 90;
                display: flex;
                max-width: calc(100vw - 1.5rem);
                transform: translateX(-50%);
                align-items: center;
                gap: .85rem;
                border: 1px solid rgba(6, 35, 43, .12);
                border-radius: 999px;
                background: rgba(6, 35, 43, .92);
                box-shadow: 0 12px 32px rgba(6, 35, 43, .28);
                color: #e8f4f6;
                padding: .55rem .55rem .55rem 1.1rem;
                backdrop-filter: blur(10px);
            }

            .reputalis-admin-preview-bar-text {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-size: .875rem;
                font-weight: 600;
            }

            .reputalis-admin-preview-bar-button {
                display: inline-flex;
                flex: 0 0 auto;
                align-items: center;
                gap: .35rem;
                border-radius: 999px;
                background: #2ad4dc;
                color: #043038;
                padding: .45rem .9rem;
                font-size: .8125rem;
                font-weight: 700;
                text-decoration: none;
                white-space: nowrap;
            }

            .reputalis-admin-preview-bar-button:hover {
                background: #5ee4ea;
            }

            html.reputalis-admin-preview .fi-main,
            html.reputalis-admin-preview .fi-body,
            body:has(.reputalis-admin-preview-bar) .fi-main,
            body:has(.reputalis-admin-preview-bar) .fi-body {
                padding-bottom: 4.75rem;
            }

            html.reputalis-staff-fullscreen .fi-sidebar,
            html.reputalis-staff-fullscreen .fi-main-sidebar,
            html.reputalis-staff-fullscreen .fi-sidebar-close-overlay,
            body.reputalis-staff-fullscreen .fi-sidebar,
            body.reputalis-staff-fullscreen .fi-main-sidebar,
            body.reputalis-staff-fullscreen .fi-sidebar-close-overlay {
                display: none !important;
                width: 0 !important;
                min-width: 0 !important;
                overflow: hidden !important;
            }

            html.reputalis-staff-fullscreen .fi-main-ctn,
            html.reputalis-staff-fullscreen .fi-layout,
            body.reputalis-staff-fullscreen .fi-main-ctn,
            body.reputalis-staff-fullscreen .fi-layout {
                margin-inline-start: 0 !important;
                padding-inline-start: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            html.reputalis-staff-fullscreen .fi-topbar-open-sidebar-btn,
            html.reputalis-staff-fullscreen .fi-topbar-close-sidebar-btn,
            body.reputalis-staff-fullscreen .fi-topbar-open-sidebar-btn,
            body.reputalis-staff-fullscreen .fi-topbar-close-sidebar-btn {
                display: none !important;
            }

            html.reputalis-admin-preview .fi-page-sub-navigation-nav-container,
            body:has(.reputalis-admin-preview-bar) .fi-page-sub-navigation-nav-container {
                display: none !important;
            }

            @media (max-width: 640px) {
                .reputalis-admin-preview-bar {
                    left: .75rem;
                    right: .75rem;
                    max-width: none;
                    transform: none;
                    border-radius: 1.15rem;
                    padding: .75rem;
                    flex-wrap: wrap;
                }

                .reputalis-admin-preview-bar-button {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
    @endif
@endif
