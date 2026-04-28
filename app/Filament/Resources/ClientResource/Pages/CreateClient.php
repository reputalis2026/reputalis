<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\ClientImprovementConfig;
use App\Models\ClientImprovementOption;
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

        // Generar código: CLIEN + 6 dígitos secuenciales (incluyendo soft-deleted)
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
        // Vincular usuario propietario con el cliente: users.client_id = clients.id
        if ($this->createdOwnerId && $this->record->id) {
            DB::table('users')->where('id', $this->createdOwnerId)->update(['client_id' => $this->record->id]);
        }

        $this->ensureDefaultImprovementConfig();

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

    /**
     * Crea la configuración inicial de encuesta del cliente si aún no existe.
     */
    private function ensureDefaultImprovementConfig(): void
    {
        $clientId = $this->record->id;
        if (! $clientId) {
            return;
        }

        $config = ClientImprovementConfig::query()
            ->where('client_id', $clientId)
            ->first();

        if ($config) {
            return;
        }

        $defaultQuestions = ClientImprovementConfig::defaultSurveyQuestionTexts();
        $defaultTitles = ClientImprovementConfig::defaultTitles();

        $config = ClientImprovementConfig::query()->create([
            'id' => (string) Str::uuid(),
            'client_id' => $clientId,
            'default_locale' => ClientImprovementConfig::DEFAULT_LOCALE,
            'title' => $defaultTitles['es'],
            'title_es' => $defaultTitles['es'],
            'title_pt' => $defaultTitles['pt'],
            'title_en' => $defaultTitles['en'],
            'survey_question_text' => $defaultQuestions['es'],
            'survey_question_text_es' => $defaultQuestions['es'],
            'survey_question_text_pt' => $defaultQuestions['pt'],
            'survey_question_text_en' => $defaultQuestions['en'],
        ]);

        ClientImprovementOption::query()->insert(
            collect(ClientImprovementOption::defaultLabels())->map(fn (array $labels, int $index): array => [
                'id' => (string) Str::uuid(),
                'client_improvement_config_id' => $config->id,
                'label' => $labels['es'],
                'label_es' => $labels['es'],
                'label_pt' => $labels['pt'],
                'label_en' => $labels['en'],
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );
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
