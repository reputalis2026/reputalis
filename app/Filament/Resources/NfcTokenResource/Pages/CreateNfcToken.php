<?php

namespace App\Filament\Resources\NfcTokenResource\Pages;

use App\Filament\Resources\NfcTokenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNfcToken extends CreateRecord
{
    protected static string $resource = NfcTokenResource::class;

    public function getTitle(): string
    {
        return 'Nuevo token NFC';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()?->isClientOwner() && auth()->user()->ownedClient) {
            $data['client_id'] = auth()->user()->ownedClient->id;
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
