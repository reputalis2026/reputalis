<?php

namespace App\Filament\Resources\DistributorResource\Pages;

use App\Filament\Resources\DistributorResource;
use App\Models\Client;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateDistributor extends CreateRecord
{
    protected static string $resource = DistributorResource::class;

    private ?array $formStateSnapshot = null;

    private ?string $createdOwnerId = null;

    public function create(bool $another = false): void
    {
        $this->formStateSnapshot = is_array($this->data) ? $this->data : [];
        parent::create($another);
    }

    public function getTitle(): string
    {
        return __('panel.distributors.create_title');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $state = $this->formStateSnapshot ?? (is_array($this->data) ? $this->data : []);
        $ownerData = [
            'email' => $state['access_email'] ?? $data['access_email'] ?? null,
            'admin_email' => $state['admin_email'] ?? $data['admin_email'] ?? null,
            'password' => $state['access_password'] ?? $data['access_password'] ?? null,
            'fullname' => $state['admin_name'] ?? $state['access_email'] ?? $data['admin_name'] ?? $data['access_email'] ?? null,
            'dni' => $state['admin_dni'] ?? $data['admin_dni'] ?? null,
            'role' => User::ROLE_DISTRIBUIDOR,
        ];

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

        $data['code'] = $this->nextClientCode();

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
        if ($this->createdOwnerId && $this->record->id) {
            DB::table('users')->where('id', $this->createdOwnerId)->update(['client_id' => $this->record->id]);
        }

        Notification::make()
            ->success()
            ->title(__('panel.distributors.notifications.created_title'))
            ->body(__('panel.distributors.notifications.created_body'))
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label(__('common.actions.create')),
            $this->getCancelFormAction()->label(__('common.actions.cancel')),
        ];
    }

    /**
     * Genera el siguiente código CLIENXXXXXX considerando también registros soft-deleted.
     */
    protected function nextClientCode(): string
    {
        $maxCode = Client::withTrashed()
            ->where('code', 'like', 'CLIEN%')
            ->orderBy('code', 'desc')
            ->value('code');

        $nextNum = $maxCode ? ((int) substr($maxCode, 5)) + 1 : 1;

        do {
            $code = 'CLIEN'.str_pad((string) $nextNum, 6, '0', STR_PAD_LEFT);
            $exists = Client::withTrashed()->where('code', $code)->exists();
            $nextNum++;
        } while ($exists);

        return $code;
    }
}
