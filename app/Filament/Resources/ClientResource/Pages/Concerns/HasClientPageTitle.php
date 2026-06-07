<?php

namespace App\Filament\Resources\ClientResource\Pages\Concerns;

use Illuminate\Contracts\Support\Htmlable;

trait HasClientPageTitle
{
    public function getTitle(): string|Htmlable
    {
        return (string) ($this->getRecord()?->namecommercial ?? __('client.resource.model_label'));
    }
}
