<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\EmployeeResource;
use App\Models\Client;
use App\Models\NfcToken;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string
    {
        return __('employees.title.create');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    /**
     * La creación llega casi siempre con ?client_id=... desde Cliente → Empleados.
     * Autorizar con la misma regla que la ficha del cliente (canEdit), no solo canCreate(),
     * para que el propietario o el distribuidor puedan añadir empleados aunque ownedClient
     * no esté resuelto y clientes sigan inactivos (is_active = false).
     */
    protected function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $clientId = request()->query('client_id');
        if (filled($clientId)) {
            $client = Client::query()->find($clientId);
            abort_unless($client, 404);
            abort_unless(ClientResource::canEdit($client), 403);

            return;
        }

        parent::authorizeAccess();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user?->isDistributor() && filled($data['client_id'] ?? null)) {
            Client::query()
                ->whereKey($data['client_id'])
                ->where('created_by', $user->id)
                ->firstOrFail();
        }
        if ($user?->isClientOwner() && filled($data['client_id'] ?? null)) {
            $owned = $user->ownedClient;
            if (! $owned || (string) $data['client_id'] !== (string) $owned->id) {
                abort(403);
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        $record = $this->getRecord();
        if ($record?->client_id && class_exists(\App\Filament\Resources\ClientResource::class)) {
            $client = Client::find($record->client_id);
            if ($client && \App\Filament\Resources\ClientResource::canView($client)) {
                return \App\Filament\Resources\ClientResource::getUrl('empleados', ['record' => $record->client_id]);
            }
        }

        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $employee = $this->getRecord();
        if (! $employee || $employee->nfcTokens()->exists()) {
            return;
        }

        $token = $this->generateUniqueToken();
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
