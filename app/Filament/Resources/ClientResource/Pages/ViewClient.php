<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ClientResource\Pages\Concerns\HasClientPageTitle;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewClient extends ViewRecord
{
    use HasClientPageTitle;

    protected static string $resource = ClientResource::class;
    protected static ?string $navigationIcon = '';

    public static function getNavigationLabel(): string
    {
        return __('client.menu.profile');
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
