<?php

namespace App\Filament\Resources\DistributorResource\Pages;

use App\Filament\Resources\DistributorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDistributors extends ListRecords
{
    protected static string $resource = DistributorResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('panel.distributors.navigation_label');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('panel.distributors.create_action'))
                ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false),
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->orderBy('is_active', 'desc')->orderBy('namecommercial', 'asc');
    }

    public function getTableEmptyStateHeading(): ?string
    {
        return __('panel.distributors.empty_heading');
    }

    public function getTableEmptyStateDescription(): ?string
    {
        return __('panel.distributors.empty_description');
    }
}
