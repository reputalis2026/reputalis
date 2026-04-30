<?php

namespace App\Filament\Resources\NfcTokenResource\Pages;

use App\Filament\Resources\NfcTokenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNfcToken extends CreateRecord
{
    protected static string $resource = NfcTokenResource::class;

    public function getTitle(): string
    {
        return __('panel.nfc_tokens.create_title');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
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
