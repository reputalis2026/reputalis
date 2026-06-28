<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Widgets\ClientsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;

class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        $user = auth()->user();
        $client = $user?->isClientOwner() ? $user->ownedClient : null;

        if ($client && ClientResource::canView($client)) {
            $this->redirect(ClientResource::getUrl('dashboard', ['record' => $client]));
        }
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.navigation_label');
    }

    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            ClientsOverviewWidget::class,
        ];
    }
}
