<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    public function getRecord(): Model
    {
        $record = parent::getRecord();
        $record->loadMissing('owner');

        return $record;
    }

    public function getTitle(): string
    {
        return 'Ver Cliente';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('puntosDeMejora')
                ->label('Puntos de mejora')
                ->icon('heroicon-o-light-bulb')
                ->url(ClientResource::getUrl('puntos-de-mejora', ['record' => $this->record]))
                ->visible(fn () => ClientResource::canView($this->record)),
            Actions\Action::make('empleados')
                ->label('Empleados')
                ->icon('heroicon-o-user-group')
                ->url(ClientResource::getUrl('empleados', ['record' => $this->record]))
                ->visible(fn () => ClientResource::canView($this->record)),
            Actions\EditAction::make()
                ->label('Ir a Editar')
                ->url(fn () => ClientResource::getUrl('edit', ['record' => $this->record]))
                ->visible(fn () => ClientResource::canEdit($this->record)),
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->requiresConfirmation()
                ->modalHeading('Eliminar cliente')
                ->modalDescription('¿Estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Eliminar')
                ->modalCancelActionLabel('Cancelar')
                ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false)
                ->disabled(fn ($record) => $record->is_active)
                ->tooltip(fn ($record) => $record->is_active ? '¡Cliente activo! Desactiva primero' : null),
        ];
    }
}
