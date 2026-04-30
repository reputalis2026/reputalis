<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;
    protected static ?string $navigationIcon = '';

    public static function getNavigationLabel(): string
    {
        return __('client.resource.model_label');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getRecord(): Model
    {
        $record = parent::getRecord();
        $record->loadMissing('owner');

        return $record;
    }

    public function getTitle(): string
    {
        return (string) ($this->record?->namecommercial ?? __('client.resource.model_label'));
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
                ->url(fn () => ClientResource::getUrl('edit', ['record' => $this->record]))
                ->visible(fn () => ClientResource::canEdit($this->record)),
        ];
    }
}
