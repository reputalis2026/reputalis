@php
    $client = \App\Support\ClientPanel::ownedClient();
    $user = filament()->auth()->user();
    $items = filament()->getUserMenuItems();
    $logoutItem = $items['logout'] ?? null;
    $profileItem = $items['profile'] ?? null;
    $items = \Illuminate\Support\Arr::except($items, ['account', 'logout', 'profile']);
    $footerName = $client?->namecommercial
        ?: ($user?->getFilamentName() ?: ($user?->fullname ?: $user?->name));
    $footerSub = ($client && filled($client->ciudad))
        ? $client->ciudad
        : match ($user?->role) {
            \App\Models\User::ROLE_SUPERADMIN => __('panel.roles.superadmin'),
            \App\Models\User::ROLE_DISTRIBUIDOR => __('panel.roles.distributor'),
            \App\Models\User::ROLE_CLIENTE => __('panel.roles.client'),
            default => $user?->email,
        };
@endphp
@if ($user)
    <div class="reputalis-client-sidebar-footer">
        <x-filament::dropdown
            placement="top-start"
            teleport
            width="xs"
            class="fi-user-menu reputalis-client-sidebar-user-menu"
        >
            <x-slot name="trigger">
                <button
                    type="button"
                    class="reputalis-client-sidebar-account"
                    aria-label="{{ __('filament-panels::layout.actions.open_user_menu.label') }}"
                >
                    <x-filament-panels::avatar.user :user="$user" class="reputalis-client-sidebar-avatar" />
                    <span class="reputalis-client-sidebar-account-text">
                        <span class="reputalis-client-sidebar-footer-name">{{ $footerName }}</span>
                        @if (filled($footerSub))
                            <span class="reputalis-client-sidebar-footer-city">{{ $footerSub }}</span>
                        @endif
                    </span>
                </button>
            </x-slot>

            <x-filament::dropdown.list>
                @if ($profileItem)
                    <x-filament::dropdown.list.item
                        :href="$profileItem->getUrl()"
                        :icon="$profileItem->getIcon() ?? 'heroicon-m-user-circle'"
                        tag="a"
                    >
                        {{ $profileItem->getLabel() ?? __('filament-panels::layout.actions.profile.label') }}
                    </x-filament::dropdown.list.item>
                @endif

                @foreach ($items as $item)
                    @php
                        $itemPostAction = $item->getPostAction();
                    @endphp
                    <x-filament::dropdown.list.item
                        :action="$itemPostAction"
                        :color="$item->getColor()"
                        :href="$item->getUrl()"
                        :icon="$item->getIcon()"
                        :method="filled($itemPostAction) ? 'post' : null"
                        :tag="filled($itemPostAction) ? 'form' : 'a'"
                        :target="$item->shouldOpenUrlInNewTab() ? '_blank' : null"
                    >
                        {{ $item->getLabel() }}
                    </x-filament::dropdown.list.item>
                @endforeach

                <x-filament::dropdown.list.item
                    :action="$logoutItem?->getUrl() ?? filament()->getLogoutUrl()"
                    :color="$logoutItem?->getColor()"
                    :icon="$logoutItem?->getIcon() ?? \Filament\Support\Facades\FilamentIcon::resolve('panels::user-menu.logout-button') ?? 'heroicon-m-arrow-left-on-rectangle'"
                    method="post"
                    tag="form"
                >
                    {{ $logoutItem?->getLabel() ?? __('filament-panels::layout.actions.logout.label') }}
                </x-filament::dropdown.list.item>
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    </div>
@endif
