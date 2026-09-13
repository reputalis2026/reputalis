<?php

namespace App\Support;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ClientResource\Pages\ReputacionExterna;
use App\Models\Client;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

class ClientPanel
{
    public static function isActive(): bool
    {
        $user = auth()->user();

        return $user?->isClientOwner() === true && $user->ownedClient !== null;
    }

    public static function ownedClient(): ?Client
    {
        if (! self::isActive()) {
            return null;
        }

        return auth()->user()->ownedClient;
    }

    /**
     * @return array<int, NavigationGroup>
     */
    public static function navigationGroups(): array
    {
        return [
            NavigationGroup::make(fn (): string => __('client.nav.groups.main'))
                ->collapsible(false),
            NavigationGroup::make(fn (): string => __('client.nav.groups.management'))
                ->collapsible(false),
            NavigationGroup::make(fn (): string => __('client.nav.groups.documents'))
                ->collapsible(false),
        ];
    }

    /**
     * @return array<int, NavigationItem>
     */
    public static function navigationItems(): array
    {
        return [
            NavigationItem::make(fn (): string => __('client.dashboard.tabs.internal'))
                ->icon('heroicon-o-check-circle')
                ->group(fn (): string => __('client.nav.groups.main'))
                ->sort(1)
                ->visible(fn (): bool => self::isActive())
                ->url(fn (): string => self::internalUrl() ?? '#')
                ->isActiveWhen(fn (): bool => self::isInternalActive()),
            NavigationItem::make(fn (): string => __('client.dashboard.tabs.external'))
                ->icon('heroicon-o-arrow-trending-up')
                ->group(fn (): string => __('client.nav.groups.main'))
                ->sort(2)
                ->visible(fn (): bool => self::isActive())
                ->url(fn (): string => self::externalUrl() ?? '#')
                ->isActiveWhen(fn (): bool => self::isExternalActive()),
            NavigationItem::make(fn (): string => __('client.dashboard.tabs.sector'))
                ->icon('heroicon-o-chart-bar')
                ->group(fn (): string => __('client.nav.groups.main'))
                ->sort(3)
                ->visible(fn (): bool => self::isActive())
                ->url(fn (): string => self::sectorUrl() ?? '#')
                ->isActiveWhen(fn (): bool => self::isSectorActive()),
        ];
    }

    public static function internalUrl(): ?string
    {
        $client = self::ownedClient();

        return $client ? ClientResource::getUrl('dashboard', ['record' => $client]) : null;
    }

    public static function externalUrl(): ?string
    {
        $client = self::ownedClient();

        return $client ? ReputacionExterna::getUrl(['record' => $client]) : null;
    }

    public static function sectorUrl(): ?string
    {
        $internal = self::internalUrl();

        return $internal ? $internal.'?reputationTab=sector' : null;
    }

    public static function isInternalActive(): bool
    {
        return self::isDashboardRoute() && request()->query('reputationTab') !== 'sector';
    }

    public static function isSectorActive(): bool
    {
        return self::isDashboardRoute() && request()->query('reputationTab') === 'sector';
    }

    public static function isExternalActive(): bool
    {
        $url = self::externalUrl();

        return filled($url) && url()->current() === rtrim($url, '/');
    }

    private static function isDashboardRoute(): bool
    {
        $url = self::internalUrl();

        return filled($url) && url()->current() === rtrim($url, '/');
    }
}
