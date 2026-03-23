<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\ClientImprovementConfig;
use App\Models\ClientImprovementOption;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Str;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\DB;

class PuntosDeMejora extends Page
{
    use InteractsWithForms;
    use InteractsWithFormActions;
    use InteractsWithRecord;

    protected static string $resource = ClientResource::class;

    protected static string $view = 'filament.resources.client-resource.pages.puntos-de-mejora';

    protected static ?string $title = 'Puntos de mejora';

    protected static ?string $navigationLabel = 'Puntos de mejora';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
        if ($this->canEditPuntos()) {
            $this->fillForm();
        }
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::Full;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function authorizeAccess(): void
    {
        $record = $this->getRecord();
        if (! ClientResource::canView($record) && ! ClientResource::canEdit($record)) {
            abort(403);
        }
    }

    /**
     * Solo SuperAdmin y Distribuidor pueden editar. Cliente solo lectura.
     */
    protected function canEditPuntos(): bool
    {
        $user = auth()->user();
        $record = $this->getRecord();
        if (! $user || ! $record) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isDistributor()) {
            return $record->created_by === $user->id;
        }

        return false;
    }

    protected function fillForm(): void
    {
        $client = $this->getRecord();
        $config = $client->improvementConfig;
        $options = $config ? $config->options()->orderBy('sort_order')->orderBy('created_at')->get() : collect();

        $this->form->fill([
            'title' => $config?->title ?? '¿En qué podemos mejorar?',
            'options' => $options->map(fn ($o) => ['label' => $o->label])->all(),
        ]);
    }

    public function form(Form $form): Form
    {
        $readOnly = ! $this->canEditPuntos();

        return $form
            ->schema([
                \Filament\Forms\Components\Section::make('Configuración de puntos de mejora')
                    ->description($readOnly
                        ? 'Título y respuestas que verá el usuario en la encuesta (solo lectura).'
                        : 'Define el título y las respuestas que verá el usuario cuando la encuesta sea negativa (1–3). Mínimo 2 respuestas.')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('title')
                            ->label('Título del bloque')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('¿En qué podemos mejorar?')
                            ->disabled($readOnly),
                        \Filament\Forms\Components\Repeater::make('options')
                            ->label('Respuestas / opciones')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('label')
                                    ->label('Texto de la respuesta')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled($readOnly),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Añadir respuesta')
                            ->minItems(2)
                            ->addable(! $readOnly)
                            ->deletable(! $readOnly)
                            ->reorderable(! $readOnly)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless($this->canEditPuntos(), 403);

        $client = $this->getRecord();
        $data = $this->form->getState();
        $title = trim((string) ($data['title'] ?? ''));
        $optionsData = $data['options'] ?? [];

        if ($title === '') {
            Notification::make()->danger()->title('El título es obligatorio')->send();

            return;
        }

        $labels = array_values(array_filter(array_map(function ($o) {
            $l = isset($o['label']) ? trim((string) $o['label']) : '';

            return $l === '' ? null : $l;
        }, $optionsData)));

        if (count($labels) < 2) {
            Notification::make()->danger()->title('Se necesitan al menos 2 respuestas')->send();

            return;
        }

        DB::transaction(function () use ($client, $title, $labels): void {
            $config = ClientImprovementConfig::firstOrNew(['client_id' => $client->id]);
            if (! $config->exists) {
                $config->id = (string) Str::uuid();
            }
            $config->title = $title;
            $config->save();

            $configId = $config->getKey();
            ClientImprovementOption::where('client_improvement_config_id', $configId)->delete();

            foreach ($labels as $i => $label) {
                ClientImprovementOption::create([
                    'client_improvement_config_id' => $configId,
                    'label' => $label,
                    'sort_order' => $i,
                ]);
            }
        });

        Notification::make()
            ->success()
            ->title('Puntos de mejora guardados')
            ->send();
    }

    protected function getFormActions(): array
    {
        if (! $this->canEditPuntos()) {
            return [];
        }

        return [
            \Filament\Actions\Action::make('save')
                ->label('Guardar')
                ->submit('save'),
        ];
    }

    public function getTitle(): string
    {
        $user = auth()->user();
        if ($user?->isClientOwner()) {
            return 'Tus puntos de mejora';
        }

        return 'Puntos de mejora: ' . $this->getRecord()->namecommercial;
    }

    /**
     * Datos para la vista de solo lectura (rol cliente): título y lista de respuestas.
     *
     * @return array{title: string, options: array<int, string>}
     */
    public function getPuntosReadOnlyData(): array
    {
        $client = $this->getRecord();
        $config = $client->improvementConfig;
        $options = $config ? $config->options()->orderBy('sort_order')->orderBy('created_at')->get() : collect();

        return [
            'title' => $config?->title ?? '¿En qué podemos mejorar?',
            'options' => $options->pluck('label')->values()->all(),
        ];
    }

    public function getBreadcrumb(): string
    {
        return 'Puntos de mejora';
    }

    public function getFormStatePath(): ?string
    {
        return 'data';
    }

    /**
     * @return array<string, mixed>
     */
    public function getSubNavigationParameters(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }

    public static function getNavigationUrl(array $parameters = []): string
    {
        $record = $parameters['record'] ?? null;

        return $record
            ? ClientResource::getUrl('puntos-de-mejora', ['record' => $record])
            : ClientResource::getUrl('index');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        if (! isset($parameters['record'])) {
            return false;
        }

        return ClientResource::canView($parameters['record']) || ClientResource::canEdit($parameters['record']);
    }
}
