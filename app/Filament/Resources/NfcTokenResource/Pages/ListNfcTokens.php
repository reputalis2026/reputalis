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
        return 'Tokens NFC';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo token NFC'),
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
