@php
    $brandName = filament()->getBrandName();
    $brandLogo = filament()->getBrandLogo();
    $darkModeBrandLogo = filament()->getDarkModeBrandLogo();
    $hasDarkModeBrandLogo = filled($darkModeBrandLogo);

    $getLogoWrapperClasses = fn (bool $isDarkMode): string => \Illuminate\Support\Arr::toCssClasses([
        'fi-logo flex items-center shrink-0',
        'flex dark:hidden' => $hasDarkModeBrandLogo && (! $isDarkMode),
        'hidden dark:flex' => $hasDarkModeBrandLogo && $isDarkMode,
    ]);
@endphp

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
                $attributes->class([
                    $getLogoWrapperClasses($isDarkMode),
                    'text-xl font-bold leading-5 tracking-tight text-gray-950 dark:text-white',
                ])
            }}
        >
            {{ $brandName }}
        </div>
    @endif
@endcapture

{{ $content($brandLogo) }}

@if ($hasDarkModeBrandLogo)
    {{ $content($darkModeBrandLogo, isDarkMode: true) }}
@endif
