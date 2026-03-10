<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string
    {
        return 'Nuevo Empleado';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user?->isClientOwner() && $user->ownedClient) {
            $data['client_id'] = $user->ownedClient->id;
        }
        if (request()->filled('client_id')) {
            $data['client_id'] = request()->input('client_id');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        $clientId = request()->query('client_id');
        $client = $clientId ? \App\Models\Client::find($clientId) : null;
        if ($client && \App\Filament\Resources\ClientResource::canView($client)) {
            return \App\Filament\Resources\ClientResource::getUrl('empleados', ['record' => $clientId]);
        }

        return $this->getResource()::getUrl('index');
    }
}
