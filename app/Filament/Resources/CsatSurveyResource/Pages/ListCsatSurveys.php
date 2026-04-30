<?php

namespace App\Filament\Resources\CsatSurveyResource\Pages;

use App\Filament\Resources\CsatSurveyResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCsatSurveys extends ListRecords
{
    protected static string $resource = CsatSurveyResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('survey.resource.navigation_label');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $user = auth()->user();

        if ($user?->isClientOwner() && $user->ownedClient) {
            return $query->where('client_id', $user->ownedClient->id);
        }

        if ($user?->isDistributor()) {
            return $query->whereHas('client', fn (Builder $q) => $q->where('created_by', $user->id));
        }

        return $query;
    }
}
