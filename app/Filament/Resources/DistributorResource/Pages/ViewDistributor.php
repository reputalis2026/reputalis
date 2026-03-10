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
        return 'Ver Distribuidor';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Ir a Editar')
                ->url(fn () => DistributorResource::getUrl('edit', ['record' => $this->record])),
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->requiresConfirmation()
                ->modalHeading('Eliminar distribuidor')
                ->modalDescription('¿Estás seguro de que deseas eliminar este distribuidor? Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Eliminar')
                ->modalCancelActionLabel('Cancelar')
                ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false)
                ->disabled(fn ($record) => $record->is_active)
                ->tooltip(fn ($record) => $record->is_active ? 'Desactiva primero' : null),
        ];
    }
}
