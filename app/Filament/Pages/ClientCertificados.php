<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ClientCertificados extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('client.nav.groups.documents');
    }

    protected static string $view = 'filament.pages.client-certificados';

    public static function getNavigationLabel(): string
    {
        return __('client.menu.certificates');
    }

    public function getTitle(): string
    {
        return __('client.certificates.title');
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
