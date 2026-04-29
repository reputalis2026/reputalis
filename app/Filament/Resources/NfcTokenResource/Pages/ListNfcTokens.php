<?php

namespace App\Filament\Resources\NfcTokenResource\Pages;

use App\Filament\Resources\NfcTokenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListNfcTokens extends ListRecords
{
    protected static string $resource = NfcTokenResource::class;

    public function getTitle(): string
    {
        return __('panel.nfc_tokens.navigation_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('panel.nfc_tokens.create_title')),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $user = auth()->user();

        if ($user?->isClientOwner() && $user->ownedClient) {
            return $query->where('client_id', $user->ownedClient->id);
        }

        return $query;
    }
}
