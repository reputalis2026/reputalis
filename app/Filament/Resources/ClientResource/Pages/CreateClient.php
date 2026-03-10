<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use App\Support\PanelMessageService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    /** Datos del propietario a crear en afterCreate. */
    private ?array $pendingOwnerData = null;

    /** Estado del formulario capturado al inicio de create() para asegurar tener acceso y admin. */
    private ?array $formStateSnapshot = null;

    /** users.id del propietario creado antes del cliente (para actualizar users.client_id en afterCreate). */
    private ?string $createdOwnerId = null;

    public function create(bool $another = false): void
    {
        // Capturar estado del formulario al inicio (Livewire ya hidrató $this->data con el request)
        $this->formStateSnapshot = is_array($this->data) ? $this->data : [];
        parent::create($another);
    }

    public function getTitle(): string
    {
        return 'Nuevo Cliente';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Prioridad: snapshot al inicio de create() > $this->data > $data de getState()
        $state = $this->formStateSnapshot ?? (is_array($this->data) ? $this->data : []);
        $ownerData = [
            'email' => $state['access_email'] ?? $data['access_email'] ?? null,
            'admin_email' => $state['admin_email'] ?? $data['admin_email'] ?? null,
            'password' => $state['access_password'] ?? $data['access_password'] ?? null,
            'fullname' => $state['admin_name'] ?? $state['access_email'] ?? $data['admin_name'] ?? $data['access_email'] ?? null,
            'dni' => $state['admin_dni'] ?? $data['admin_dni'] ?? null,
            'role' => User::ROLE_CLIENTE,
        ];
        $this->pendingOwnerData = $ownerData;

        // Crear el usuario (tabla users) UNA sola vez; clients.owner_id = users.id de ese usuario
        if (! empty($ownerData['email']) && ! empty($ownerData['password'])) {
            if (! $this->createdOwnerId) {
                $ownerId = (string) Str::uuid();
                $fullname = $ownerData['fullname'] ?: $ownerData['email'];
                User::create([
                    'id' => $ownerId,
                    'email' => $ownerData['email'],
                    'admin_email' => $ownerData['admin_email'],
                    'password' => $ownerData['password'],
                    'fullname' => $fullname,
                    'name' => explode(' ', $fullname)[0] ?? $fullname,
                    'dni' => $ownerData['dni'],
                    'role' => $ownerData['role'],
                    'client_id' => null,
                    'email_verified_at' => now(),
                ]);
                $this->createdOwnerId = $ownerId;
            }
            $data['owner_id'] = $this->createdOwnerId;
        }

        // Generar código: CLIEN + 6 dígitos secuenciales
        $maxCode = Client::where('code', 'like', 'CLIEN%')->orderBy('code', 'desc')->value('code');
        $nextNum = $maxCode ? ((int) substr($maxCode, 5)) + 1 : 1;
        $data['code'] = 'CLIEN' . str_pad((string) $nextNum, 6, '0', STR_PAD_LEFT);

        $data['fecha_inicio_alta'] = $data['fecha_inicio_alta'] ?? now()->toDateString();
        $data['is_active'] = false;
        $data['created_by'] = auth()->id();

        unset(
            $data['admin_email'],
            $data['admin_name'],
            $data['admin_dni'],
            $data['access_email'],
            $data['access_password'],
            $data['access_password_confirmation'],
            $data['show_password']
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        // Vincular usuario propietario con el cliente: users.client_id = clients.id
        if ($this->createdOwnerId && $this->record->id) {
            DB::table('users')->where('id', $this->createdOwnerId)->update(['client_id' => $this->record->id]);
        }

        // Si un distribuidor creó el cliente, notificar a SuperAdmins (cliente queda inactivo hasta activación)
        if (auth()->user()?->isDistributor()) {
            PanelMessageService::notifyClientPendingActivation($this->record);
        }

        Notification::make()
            ->success()
            ->title('Cliente creado')
            ->body('El cliente y el usuario administrador han sido creados exitosamente.')
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Crear'),
            $this->getCancelFormAction()
                ->label('Cancelar'),
        ];
    }
}
