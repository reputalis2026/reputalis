<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string
    {
        return 'Editar Empleado';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Ver'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->record->client_id && \App\Filament\Resources\ClientResource::canView($this->record->client)) {
            return \App\Filament\Resources\ClientResource::getUrl('empleados', ['record' => $this->record->client_id]);
        }

        return $this->getResource()::getUrl('index');
    }
}
