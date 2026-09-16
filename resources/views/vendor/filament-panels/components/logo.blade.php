<style>
    .fi-sidebar-header .fi-logo,
    .fi-topbar .fi-logo {
        max-width: none;
        overflow: visible;
        pointer-events: none;
        cursor: default;
    }

    .fi-sidebar-header a:has(.fi-logo),
    .fi-topbar a:has(.fi-logo) {
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
</style>

<div class="fi-logo reputalis-client-brand">
    <span class="reputalis-client-brand-name">Reputalis</span>
    <span class="reputalis-client-brand-tagline">{{ __('client.nav.tagline') }}</span>
</div>
