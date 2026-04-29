<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Models\NfcToken;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string
    {
        return __('employees.title.edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label(__('common.actions.view')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->record->client_id && \App\Filament\Resources\ClientResource::canView($this->record->client)) {
            return \App\Filament\Resources\ClientResource::getUrl('empleados', ['record' => $this->record->client_id]);
        }

        return $this->getResource()::getUrl('index');
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
            'is_active' => true,
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
