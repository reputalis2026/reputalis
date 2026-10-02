@php
    $brandName = filament()->getBrandName();
    $isLoginOrGuest = ! filament()->auth()->check();
    $logoUrl = asset('img/logoReputalis.png');
@endphp

<style>
    .fi-sidebar-header .fi-logo,
    .fi-topbar .fi-logo,
    .fi-simple-main .fi-logo {
        max-width: none;
        overflow: visible;
        pointer-events: none;
        cursor: default;
    }

    .fi-sidebar-header a:has(.fi-logo),
    .fi-topbar a:has(.fi-logo),
    .fi-simple-main a:has(.fi-logo) {
        pointer-events: none;
        cursor: default;
        text-decoration: none;
    }

    .fi-sidebar-header:has(.reputalis-client-brand) {
        height: auto !important;
        min-height: 4rem;
        align-items: center;
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }

    .fi-simple-main .reputalis-login-logo {
        display: block;
        height: 2.75rem;
        width: auto;
        max-width: min(100%, 16rem);
        object-fit: contain;
        margin-inline: auto;
    }
</style>

@if ($isLoginOrGuest)
    <img
        src="{{ $logoUrl }}"
        alt="{{ $brandName }}"
        class="fi-logo reputalis-login-logo"
        width="280"
        height="44"
        decoding="async"
    >
@else
    <div class="fi-logo reputalis-client-brand">
        <span class="reputalis-client-brand-name">Reputalis</span>
        <span class="reputalis-client-brand-tagline">{{ __('client.nav.tagline') }}</span>
    </div>
@endif
