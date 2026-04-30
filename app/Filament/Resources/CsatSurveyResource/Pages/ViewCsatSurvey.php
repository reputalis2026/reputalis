<?php

namespace App\Filament\Resources\CsatSurveyResource\Pages;

use App\Filament\Resources\CsatSurveyResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCsatSurvey extends ViewRecord
{
    protected static string $resource = CsatSurveyResource::class;

    public function getTitle(): string
    {
        return __('survey.resource.view_title');
    }

    public function getBreadcrumbs(): array
    {
        return [];
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
