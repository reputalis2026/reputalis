<?php

namespace App\Filament\Resources\DistributorResource\Pages;

use App\Filament\Resources\DistributorResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDistributor extends EditRecord
{
    protected static string $resource = DistributorResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->loadMissing('owner');
    }

    public function getTitle(): string
    {
        return __('panel.distributors.edit_title');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('owner');
        if ($this->record->owner) {
            $data['admin_email'] = $this->record->owner->admin_email ?? $this->record->owner->email;
            $data['admin_name'] = $this->record->owner->fullname;
            $data['admin_dni'] = $this->record->owner->dni;
            $data['access_email'] = $this->record->owner->email;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $ownerPassword = $data['access_password'] ?? null;
        $ownerName = $data['admin_name'] ?? null;
        $ownerDni = $data['admin_dni'] ?? null;
        $adminEmail = $data['admin_email'] ?? null;
        $raw = $this->form->getRawState();
        $raw = is_array($raw) ? $raw : (is_object($raw) && method_exists($raw, 'toArray') ? $raw->toArray() : []);
        $showPassword = (bool) ($raw['show_password'] ?? false);

        unset(
            $data['admin_email'],
            $data['admin_name'],
            $data['admin_dni'],
            $data['access_email'],
            $data['access_password'],
            $data['access_password_confirmation'],
            $data['show_password']
        );

        if (empty($data['fecha_inicio_alta']) && $this->record->fecha_inicio_alta) {
            $data['fecha_inicio_alta'] = $this->record->fecha_inicio_alta;
        }

        if ($this->record->owner) {
            $owner = $this->record->owner;
            if ($ownerName) {
                $owner->fullname = $ownerName;
                $owner->name = explode(' ', $ownerName)[0] ?? $ownerName;
            }
            if ($ownerDni !== null) {
                $owner->dni = $ownerDni;
            }
            if ($adminEmail !== null) {
                $owner->admin_email = $adminEmail;
            }
            if ($showPassword && $ownerPassword) {
                $owner->password = $ownerPassword;
            }
            $owner->save();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->success()
            ->title(__('panel.distributors.notifications.updated_title'))
            ->body(__('panel.distributors.notifications.updated_body'))
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return DistributorResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label(__('common.actions.view')),
            Actions\DeleteAction::make()
                ->label(__('common.actions.delete'))
                ->requiresConfirmation()
                ->modalHeading(__('panel.distributors.delete'))
                ->modalDescription(__('panel.distributors.delete_confirm'))
                ->modalSubmitActionLabel(__('common.actions.delete'))
                ->modalCancelActionLabel(__('common.actions.cancel'))
                ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false)
                ->disabled(fn ($record) => $record->is_active)
                ->tooltip(fn ($record) => $record->is_active ? __('panel.distributors.active_tooltip') : null),
        ];
    }
}
