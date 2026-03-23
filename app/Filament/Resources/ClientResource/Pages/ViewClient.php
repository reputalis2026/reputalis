<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;
    protected static ?string $navigationLabel = 'Cliente';
    protected static ?string $navigationIcon = '';

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
        return (string) ($this->record?->namecommercial ?? 'Cliente');
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Ir a Editar')
                ->url(fn () => ClientResource::getUrl('edit', ['record' => $this->record]))
                ->visible(fn () => ClientResource::canEdit($this->record)),
        ];
    }
}
