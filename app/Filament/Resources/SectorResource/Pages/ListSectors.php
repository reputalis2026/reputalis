<?php

namespace App\Filament\Resources\SectorResource\Pages;

use App\Filament\Resources\SectorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSectors extends ListRecords
{
    protected static string $resource = SectorResource::class;

    public function getTitle(): string
    {
        return __('panel.sectors.list_title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('panel.sectors.add')),
        ];
    }
}
