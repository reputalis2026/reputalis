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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
