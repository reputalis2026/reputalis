<?php

namespace App\Filament\Resources\NfcTokenResource\Pages;

use App\Filament\Resources\NfcTokenResource;
use Filament\Resources\Pages\EditRecord;

class EditNfcToken extends EditRecord
{
    protected static string $resource = NfcTokenResource::class;

    public function getTitle(): string
    {
        return __('panel.nfc_tokens.edit_title');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
