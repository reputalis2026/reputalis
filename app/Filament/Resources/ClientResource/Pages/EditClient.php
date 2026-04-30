<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\User;
use App\Support\PanelMessageService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    /** Si el cliente estaba inactivo antes de guardar (para detectar activación por SuperAdmin). */
    public bool $wasInactiveBeforeSave = false;

    /**
     * Estado original de expiración al cargar el formulario (para detectar cambios y mostrar confirmación).
     *
     * @var array{is_active: bool, fecha_fin: string|null, activation_duration: int|string|null}
     */
    public array $originalExpirationState = [];

    /** Mostrar modal de confirmación al cambiar la fecha de expiración (controlado por Livewire). */
    public bool $showExpirationConfirmModal = false;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->loadMissing('owner');
    }

    public function getTitle(): string
    {
        return __('client.pages.edit_title');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('owner');
        if ($this->record->owner) {
            $data['admin_email'] = $this->record->owner->admin_email;
            $data['admin_name'] = $this->record->owner->fullname;
            $data['admin_dni'] = $this->record->owner->dni;
            $data['access_email'] = $this->record->owner->email;
        }

        // Rellenar selector de duración según fecha_fin existente (solo si cliente activo con fechas)
        if ($this->record->is_active && $this->record->fecha_fin && $this->record->fecha_inicio_alta) {
            $start = $this->record->fecha_inicio_alta;
            $end = $this->record->fecha_fin;
            $months = $start->diffInMonths($end);
            if ($months >= 11 && $months <= 13) {
                $data['activation_duration'] = 12;
            } elseif ($months >= 23 && $months <= 25) {
                $data['activation_duration'] = 24;
            } elseif ($months >= 35 && $months <= 37) {
                $data['activation_duration'] = 36;
            } else {
                $data['activation_duration'] = 'custom';
            }
        }

        // Guardar estado original de expiración para detectar cambios y mostrar confirmación al guardar
        $this->originalExpirationState = [
            'is_active' => (bool) ($data['is_active'] ?? false),
            'fecha_fin' => $this->normalizeFechaFinForComparison($data['fecha_fin'] ?? null),
            'activation_duration' => $data['activation_duration'] ?? null,
        ];

        return $data;
    }

    /**
     * Normaliza fecha_fin a string Y-m-d para comparación.
     */
    private function normalizeFechaFinForComparison(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \Carbon\Carbon || $value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_string($value)) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Comprueba si el usuario ha cambiado algo relacionado con la expiración (duración o fecha de fin).
     * Solo considera cambio cuando el cliente está o queda activo.
     */
    public function hasExpirationChanged(): bool
    {
        $raw = $this->form->getRawState();
        $raw = is_array($raw) ? $raw : (is_object($raw) && method_exists($raw, 'toArray') ? $raw->toArray() : []);
        $currentActive = (bool) ($raw['is_active'] ?? false);
        if (! $currentActive) {
            return false;
        }
        $originalActive = $this->originalExpirationState['is_active'] ?? false;
        $duration = $raw['activation_duration'] ?? null;
        $fechaInicio = $raw['fecha_inicio_alta'] ?? $this->record->fecha_inicio_alta?->format('Y-m-d');
        $fechaFinRaw = $raw['fecha_fin'] ?? null;
        $effectiveFechaFin = $this->effectiveFechaFin($duration, $fechaInicio, $fechaFinRaw);
        $originalFechaFin = $this->originalExpirationState['fecha_fin'] ?? null;
        if (! $originalActive) {
            return true;
        }
        return $effectiveFechaFin !== $originalFechaFin;
    }

    /**
     * Calcula la fecha de fin efectiva según duración o fecha manual.
     */
    private function effectiveFechaFin(mixed $duration, ?string $fechaInicio, mixed $fechaFinRaw): ?string
    {
        if (is_numeric($duration) && $fechaInicio) {
            return Carbon::parse($fechaInicio)->addMonths((int) $duration)->format('Y-m-d');
        }
        return $this->normalizeFechaFinForComparison($fechaFinRaw);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->wasInactiveBeforeSave = $this->record->is_active === false;

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

        // Mantener fecha_inicio_alta (no editable; puede no venir en el estado del formulario)
        if (empty($data['fecha_inicio_alta']) && $this->record->fecha_inicio_alta) {
            $data['fecha_inicio_alta'] = $this->record->fecha_inicio_alta;
        }

        // Si se eligió duración 12/24/36 meses, calcular fecha_fin desde fecha_inicio_alta
        $raw = $this->form->getRawState();
        $raw = is_array($raw) ? $raw : (is_object($raw) && method_exists($raw, 'toArray') ? $raw->toArray() : []);
        $duration = $raw['activation_duration'] ?? $data['activation_duration'] ?? null;
        if (is_numeric($duration)) {
            $base = $data['fecha_inicio_alta'] ?? $this->record->fecha_inicio_alta;
            if ($base) {
                $data['fecha_fin'] = \Carbon\Carbon::parse($base)->addMonths((int) $duration)->toDateString();
            }
        }
        unset($data['activation_duration']);

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
        // Si SuperAdmin acaba de activar un cliente creado por distribuidor, notificar al distribuidor
        if ($this->wasInactiveBeforeSave && $this->record->is_active) {
            PanelMessageService::notifyClientActivated($this->record);
        }

        Notification::make()
            ->success()
            ->title(__('client.notifications.updated_title'))
            ->body(__('client.notifications.updated_body'))
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return ClientResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
            ->action(function (): void {
                $this->form->validate();
                if ($this->hasExpirationChanged()) {
                    $this->showExpirationConfirmModal = true;
                } else {
                    $this->save();
                }
            })
            ->keyBindings(['mod+s']);
    }

    /** Confirma y ejecuta el guardado tras aceptar en el modal de cambio de expiración. */
    public function confirmExpirationSave(): void
    {
        $this->showExpirationConfirmModal = false;
        $this->save();
    }

    /** Cierra el modal de confirmación de expiración sin guardar. */
    public function closeExpirationConfirmModal(): void
    {
        $this->showExpirationConfirmModal = false;
    }

    /**
     * @return array<Action | \Filament\Actions\ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.resources.client-resource.pages.edit-client-expiration-modal', [
            'showExpirationConfirmModal' => $this->showExpirationConfirmModal,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(__('client.actions.delete_client'))
                ->requiresConfirmation()
                ->modalHeading(__('client.actions.delete_client'))
                ->modalDescription(__('client.actions.delete_confirm'))
                ->modalSubmitActionLabel(__('common.actions.delete'))
                ->modalCancelActionLabel(__('common.actions.cancel'))
                ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false)
                ->disabled(fn ($record) => $record->is_active)
                ->tooltip(fn ($record) => $record->is_active ? __('client.actions.active_tooltip') : null),
        ];
    }

    public function getSubNavigation(): array
    {
        // En pantalla de edición no mostramos la subnavegación lateral del registro.
        return [];
    }
}
