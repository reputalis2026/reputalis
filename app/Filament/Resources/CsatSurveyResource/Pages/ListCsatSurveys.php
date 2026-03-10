<?php

namespace App\Filament\Resources\CsatSurveyResource\Pages;

use App\Filament\Resources\CsatSurveyResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCsatSurveys extends ListRecords
{
    protected static string $resource = CsatSurveyResource::class;

    public function getTitle(): string
    {
        return 'Encuestas CSAT';
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
