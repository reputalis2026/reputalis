<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ClientInformes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('client.nav.groups.documents');
    }

    protected static string $view = 'filament.pages.client-informes';

    public static function getNavigationLabel(): string
    {
        return __('client.menu.reports');
    }

    public function getTitle(): string
    {
        return __('client.reports.title');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user?->isClientOwner() === true && $user->ownedClient !== null;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isClientOwner() === true && $user->ownedClient !== null;
    }
}
