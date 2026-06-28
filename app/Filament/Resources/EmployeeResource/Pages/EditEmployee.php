<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\ClientResource;
use App\Models\NfcToken;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string
    {
        return __('employees.title.edit');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->record->client_id && ClientResource::canView($this->record->client)) {
            return ClientResource::getUrl('empleados', ['record' => $this->record->client_id]);
        }

        return ClientResource::getUrl('index');
    }

    /**
     * Regla 1–1 dominio: aseguramos que el empleado tenga su token NFC.
     */
    protected function afterSave(): void
    {
        $employee = $this->getRecord();

        if (! $employee) {
            return;
        }

        if ($employee->nfcTokens()->exists()) {
            return;
        }

        $token = $this->generateUniqueToken();

        $employee->nfcTokens()->create([
            'client_id' => $employee->client_id,
            'token' => $token,
            'is_active' => (bool) $employee->is_active,
        ]);
    }

    protected function generateUniqueToken(): string
    {
        do {
            $token = Str::random(32);
        } while (NfcToken::query()->where('token', $token)->exists());

        return $token;
    }
}
