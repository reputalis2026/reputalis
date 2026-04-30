<?php

namespace App\Filament\Resources\DistributorResource\Pages;

use App\Filament\Resources\DistributorResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewDistributor extends ViewRecord
{
    protected static string $resource = DistributorResource::class;

    public function getRecord(): Model
    {
        $record = parent::getRecord();
        $record->loadMissing('owner');

        return $record;
    }

    public function getTitle(): string
    {
        return __('panel.distributors.view_title');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label(__('common.actions.go_to_edit'))
                ->url(fn () => DistributorResource::getUrl('edit', ['record' => $this->record])),
            Actions\DeleteAction::make()
                ->label(__('common.actions.delete'))
                ->requiresConfirmation()
                ->modalHeading(__('panel.distributors.delete'))
                ->modalDescription(__('panel.distributors.delete_confirm'))
                ->modalSubmitActionLabel(__('common.actions.delete'))
                ->modalCancelActionLabel(__('common.actions.cancel'))
                ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false)
                ->disabled(fn ($record) => $record->is_active)
                ->tooltip(fn ($record) => $record->is_active ? __('panel.distributors.active_tooltip') : null),
        ];
    }
}
