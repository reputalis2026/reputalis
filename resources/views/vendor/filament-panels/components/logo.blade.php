@php
    $brandName = filament()->getBrandName();
    $brandLogo = filament()->getBrandLogo();
    $isClientPanel = \App\Support\ClientPanel::isActive();
    $darkModeBrandLogo = filament()->getDarkModeBrandLogo();
    $hasDarkModeBrandLogo = filled($darkModeBrandLogo);
    $displayBrandName = \Illuminate\Support\Str::upper((string) $brandName);

    $getLogoWrapperClasses = fn (bool $isDarkMode): string => \Illuminate\Support\Arr::toCssClasses([
        'fi-logo flex items-center shrink-0',
        'flex dark:hidden' => $hasDarkModeBrandLogo && (! $isDarkMode),
        'hidden dark:flex' => $hasDarkModeBrandLogo && $isDarkMode,
    ]);
@endphp

<style>
    .fi-logo-brand-text {
        display: block;
        max-width: none;
        overflow: visible;
        white-space: normal;
        word-break: break-word;
        font-size: 1.05rem;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: inherit;
        cursor: default;
        pointer-events: none;
        user-select: none;
    }

    .dark .fi-logo-brand-text {
        color: #fff;
    }

    .fi-sidebar-header .fi-logo,
    .fi-topbar .fi-logo {
        max-width: none;
        overflow: visible;
        pointer-events: none;
        cursor: default;
    }

    /* Si Filament envuelve el logo en <a>, desactivar el clic. */
    .fi-sidebar-header a:has(.fi-logo),
    .fi-topbar a:has(.fi-logo) {
        pointer-events: none;
        cursor: default;
        text-decoration: none;
    }

    /* Permitir que el nombre largo crezca en altura en el header del sidebar. */
    .fi-sidebar-header:has(.fi-logo-brand-text) {
        height: auto !important;
        min-height: 4rem;
        align-items: center;
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }
</style>

@if ($isClientPanel)
    <div class="fi-logo reputalis-client-brand">
        <span class="reputalis-client-brand-name">Reputalis</span>
        <span class="reputalis-client-brand-tagline">{{ __('client.nav.tagline') }}</span>
    </div>
@else
@capture($content, $logo, $isDarkMode = false)
    @if ($logo instanceof \Illuminate\Contracts\Support\Htmlable)
        <div {{ $attributes->class([$getLogoWrapperClasses($isDarkMode)]) }}>
            <div class="flex items-center pl-1 [&_img]:h-8 [&_img]:w-auto [&_img]:max-w-[14rem] [&_img]:object-contain">
                {{ $logo }}
            </div>
        </div>
    @elseif (filled($logo))
        <div {{ $attributes->class([$getLogoWrapperClasses($isDarkMode)]) }}>
            <img
                src="{{ $logo }}"
                alt="{{ __('filament-panels::layout.logo.alt', ['name' => $brandName]) }}"
                class="h-8 w-auto max-w-[14rem] object-contain"
            >
        </div>
    @else
        <div
            {{
                $attributes
                    ->merge([
                        'title' => $brandName,
                    ])
                    ->class([
                        $getLogoWrapperClasses($isDarkMode),
                        'text-gray-950 dark:text-white',
                    ])
            }}
        >
            <span class="fi-logo-brand-text">
                {{ $displayBrandName }}
            </span>
        </div>
    @endif
@endcapture

{{ $content($brandLogo) }}

@if ($hasDarkModeBrandLogo)
    {{ $content($darkModeBrandLogo, isDarkMode: true) }}
@endif
@endif
