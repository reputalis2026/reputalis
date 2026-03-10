<?php

namespace App\Filament\Resources\NfcTokenResource\Pages;

use App\Filament\Resources\NfcTokenResource;
use Filament\Resources\Pages\EditRecord;

class EditNfcToken extends EditRecord
{
    protected static string $resource = NfcTokenResource::class;

    public function getTitle(): string
    {
        return 'Editar token NFC';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
