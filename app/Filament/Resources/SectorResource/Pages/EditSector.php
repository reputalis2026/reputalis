<?php

namespace App\Filament\Resources\SectorResource\Pages;

use App\Filament\Resources\SectorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSector extends EditRecord
{
    protected static string $resource = SectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(__('common.actions.delete'))
                ->before(function ($record) {
                    if (! $record->canDelete()) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title(__('panel.sectors.cannot_delete_title'))
                            ->body(__('panel.sectors.cannot_delete_body'))
                            ->send();
                        throw new \Exception(__('panel.sectors.cannot_delete_title'));
                    }
                }),
        ];
    }
}
