<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\NfcToken;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

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

    /**
     * Regla 1–1 dominio: si el empleado no tiene token NFC, se crea automáticamente.
     */
    protected function afterCreate(): void
    {
        $employee = $this->getRecord();
        if (! $employee) {
            return;
        }

        if ($employee->nfcTokens()->exists()) {
            return;
        }

        $token = $this->generateUniqueToken();

        // Se crea el NfcToken asociado al empleado (1–1 por employee_id).
        $employee->nfcTokens()->create([
            'client_id' => $employee->client_id,
            'token' => $token,
            'is_active' => true,
        ]);
    }

    protected function generateUniqueToken(): string
    {
        do {
            $token = Str::random(32);
        } while (NfcToken::query()->where('token', $token)->exists());

        return $token;
    }
}
