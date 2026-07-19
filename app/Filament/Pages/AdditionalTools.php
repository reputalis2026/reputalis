<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AdditionalTools extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 80;

    protected static string $view = 'filament.pages.additional-tools';

    public static function getNavigationLabel(): string
    {
        return __('panel.additional_tools.navigation_label');
    }

    public function getTitle(): string
    {
        return __('panel.additional_tools.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel.navigation_groups.configuration');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() === true || $user?->isDistributor() === true;
    }

    public function canManageSectors(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }
}
