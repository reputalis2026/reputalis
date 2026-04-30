<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\ClientImprovementConfig;
use App\Models\ClientImprovementOption;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PuntosDeMejora extends Page
{
    use InteractsWithFormActions;
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = ClientResource::class;

    protected static string $view = 'filament.resources.client-resource.pages.puntos-de-mejora';

    public static function getNavigationLabel(): string
    {
        return __('client.menu.survey');
    }

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

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
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
        $config = $this->ensureDefaultConfigForClient($client->id);
        $options = $config ? $config->options()->orderBy('sort_order')->orderBy('created_at')->get() : collect();
        $defaultQuestions = ClientImprovementConfig::defaultSurveyQuestionTexts();
        $defaultTitles = ClientImprovementConfig::defaultTitles();

        $this->form->fill([
            'default_locale' => ClientImprovementConfig::normalizeDefaultLocale($config?->default_locale),
            'survey_question_text_es' => $config?->survey_question_text_es ?? $defaultQuestions['es'],
            'survey_question_text_pt' => $config?->survey_question_text_pt ?? $defaultQuestions['pt'],
            'survey_question_text_en' => $config?->survey_question_text_en ?? $defaultQuestions['en'],
            'title_es' => $config?->title_es ?? $defaultTitles['es'],
            'title_pt' => $config?->title_pt ?? $defaultTitles['pt'],
            'title_en' => $config?->title_en ?? $defaultTitles['en'],
            'display_mode' => ClientImprovementConfig::normalizeDisplayMode($config?->display_mode),
            'positive_scores' => $config?->positiveScores() ?? ClientImprovementConfig::defaultPositiveScores(),
            'options' => $options->map(fn ($o) => [
                'label_es' => $o->label_es ?: $o->label,
                'label_pt' => $o->label_pt ?: ($o->label_es ?: $o->label),
                'label_en' => $o->label_en ?: ($o->label_es ?: $o->label),
            ])->all(),
        ]);
    }

    private function ensureDefaultConfigForClient(string $clientId): ?ClientImprovementConfig
    {
        $config = ClientImprovementConfig::query()
            ->where('client_id', $clientId)
            ->first();

        if ($config) {
            return $config;
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
            'positive_scores' => ClientImprovementConfig::defaultPositiveScores(),
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

        return $config;
    }

    public function form(Form $form): Form
    {
        $readOnly = ! $this->canEditPuntos();

        return $form
            ->schema([
                \Filament\Forms\Components\Section::make(__('client.menu.survey'))
                    ->description($readOnly
                        ? __('client.survey.read_only_description')
                        : __('client.survey.edit_description'))
                    ->schema([
                        \Filament\Forms\Components\Radio::make('display_mode')
                            ->label(__('client.survey.display_mode_form'))
                            ->options([
                                ClientImprovementConfig::DISPLAY_MODE_NUMBERS => __('client.survey.display_modes.numbers'),
                                ClientImprovementConfig::DISPLAY_MODE_FACES => __('client.survey.display_modes.faces'),
                            ])
                            ->default(ClientImprovementConfig::DISPLAY_MODE_NUMBERS)
                            ->required()
                            ->disabled($readOnly),
                        \Filament\Forms\Components\Select::make('default_locale')
                            ->label(__('client.survey.default_public_locale'))
                            ->options([
                                'es' => __('survey.language_names.es'),
                                'pt' => __('survey.language_names.pt'),
                                'en' => __('survey.language_names.en'),
                            ])
                            ->native(false)
                            ->default(ClientImprovementConfig::DEFAULT_LOCALE)
                            ->required()
                            ->disabled($readOnly),
                        \Filament\Forms\Components\Section::make(__('client.survey.positive_scores'))
                            ->description(__('client.survey.positive_scores_help'))
                            ->schema([
                                \Filament\Forms\Components\CheckboxList::make('positive_scores')
                                    ->label(__('client.survey.positive_scores_label'))
                                    ->options([
                                        1 => '1',
                                        2 => '2',
                                        3 => '3',
                                        4 => '4',
                                        5 => '5',
                                    ])
                                    ->columns(5)
                                    ->bulkToggleable(false)
                                    ->default(ClientImprovementConfig::defaultPositiveScores())
                                    ->required()
                                    ->disabled($readOnly),
                            ]),
                        \Filament\Forms\Components\Section::make(__('client.survey.main_question_section'))
                            ->schema($this->localizedTextInputs('survey_question_text', [
                                'es' => '¿Cómo le hemos atendido hoy?',
                                'pt' => 'Como fomos no seu atendimento hoje?',
                                'en' => 'How was your experience today?',
                            ], $readOnly))
                            ->columns(3),
                        \Filament\Forms\Components\Section::make(__('client.survey.block_title_section'))
                            ->schema($this->localizedTextInputs('title', [
                                'es' => '¿En qué podemos mejorar?',
                                'pt' => 'Em que podemos melhorar?',
                                'en' => 'What can we improve?',
                            ], $readOnly))
                            ->columns(3),
                        \Filament\Forms\Components\Repeater::make('options')
                            ->label(__('client.survey.answers_options'))
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('label_es')
                                    ->label(__('survey.language_names.es'))
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled($readOnly),
                                \Filament\Forms\Components\TextInput::make('label_pt')
                                    ->label(__('survey.language_names.pt'))
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled($readOnly),
                                \Filament\Forms\Components\TextInput::make('label_en')
                                    ->label(__('survey.language_names.en'))
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled($readOnly),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel(__('client.survey.add_answer'))
                            ->minItems(2)
                            ->addable(! $readOnly)
                            ->deletable(! $readOnly)
                            ->reorderable(! $readOnly)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label_es'] ?? null),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    /**
     * @param  array{es: string, pt: string, en: string}  $placeholders
     * @return array<int, \Filament\Forms\Components\TextInput>
     */
    private function localizedTextInputs(string $fieldPrefix, array $placeholders, bool $readOnly): array
    {
        return [
            \Filament\Forms\Components\TextInput::make("{$fieldPrefix}_es")
                ->label(__('survey.language_names.es'))
                ->required()
                ->maxLength(255)
                ->placeholder($placeholders['es'])
                ->disabled($readOnly),
            \Filament\Forms\Components\TextInput::make("{$fieldPrefix}_pt")
                ->label(__('survey.language_names.pt'))
                ->required()
                ->maxLength(255)
                ->placeholder($placeholders['pt'])
                ->disabled($readOnly),
            \Filament\Forms\Components\TextInput::make("{$fieldPrefix}_en")
                ->label(__('survey.language_names.en'))
                ->required()
                ->maxLength(255)
                ->placeholder($placeholders['en'])
                ->disabled($readOnly),
        ];
    }

    public function save(): void
    {
        abort_unless($this->canEditPuntos(), 403);

        $client = $this->getRecord();
        $data = $this->form->getState();
        $defaultLocale = ClientImprovementConfig::normalizeDefaultLocale($data['default_locale'] ?? null);
        $displayMode = ClientImprovementConfig::normalizeDisplayMode($data['display_mode'] ?? null);
        $positiveScores = ClientImprovementConfig::normalizePositiveScores($data['positive_scores'] ?? []);
        $optionsData = $data['options'] ?? [];
        $surveyQuestionTexts = $this->trimLocalizedState($data, 'survey_question_text');
        $titles = $this->trimLocalizedState($data, 'title');

        if (! ClientImprovementConfig::positiveScoresAreValid($positiveScores)) {
            Notification::make()
                ->danger()
                ->title(__('client.survey.validation.positive_scores_title'))
                ->body(__('client.survey.validation.positive_scores_body'))
                ->send();

            return;
        }

        if (! $this->allLocalizedValuesFilled($surveyQuestionTexts)) {
            Notification::make()->danger()->title(__('client.survey.validation.main_question_complete'))->send();

            return;
        }

        if (! $this->allLocalizedValuesFilled($titles)) {
            Notification::make()->danger()->title(__('client.survey.validation.block_title_complete'))->send();

            return;
        }

        $labels = array_values(array_map(fn (array $option): array => $this->trimLocalizedState($option, 'label'), $optionsData));

        if (count($labels) < 2) {
            Notification::make()->danger()->title(__('client.survey.validation.min_answers'))->send();

            return;
        }

        foreach ($labels as $label) {
            if (! $this->allLocalizedValuesFilled($label)) {
                Notification::make()->danger()->title(__('client.survey.validation.answers_complete'))->send();

                return;
            }
        }

        DB::transaction(function () use ($client, $defaultLocale, $surveyQuestionTexts, $titles, $displayMode, $positiveScores, $labels): void {
            $config = ClientImprovementConfig::firstOrNew(['client_id' => $client->id]);
            if (! $config->exists) {
                $config->id = (string) Str::uuid();
            }
            $config->default_locale = $defaultLocale;
            $config->survey_question_text = $surveyQuestionTexts['es'];
            $config->survey_question_text_es = $surveyQuestionTexts['es'];
            $config->survey_question_text_pt = $surveyQuestionTexts['pt'];
            $config->survey_question_text_en = $surveyQuestionTexts['en'];
            $config->title = $titles['es'];
            $config->title_es = $titles['es'];
            $config->title_pt = $titles['pt'];
            $config->title_en = $titles['en'];
            $config->display_mode = $displayMode;
            $config->positive_scores = $positiveScores;
            $config->save();

            $configId = $config->getKey();
            ClientImprovementOption::where('client_improvement_config_id', $configId)->delete();

            foreach ($labels as $i => $label) {
                ClientImprovementOption::create([
                    'id' => (string) Str::uuid(),
                    'client_improvement_config_id' => $configId,
                    'label' => $label['es'],
                    'label_es' => $label['es'],
                    'label_pt' => $label['pt'],
                    'label_en' => $label['en'],
                    'sort_order' => $i,
                ]);
            }
        });

        Notification::make()
            ->success()
            ->title(__('client.survey.saved'))
            ->send();
    }

    /**
     * @return array{es: string, pt: string, en: string}
     */
    private function trimLocalizedState(array $state, string $fieldPrefix): array
    {
        return [
            'es' => trim((string) ($state["{$fieldPrefix}_es"] ?? '')),
            'pt' => trim((string) ($state["{$fieldPrefix}_pt"] ?? '')),
            'en' => trim((string) ($state["{$fieldPrefix}_en"] ?? '')),
        ];
    }

    /**
     * @param  array{es: string, pt: string, en: string}  $values
     */
    private function allLocalizedValuesFilled(array $values): bool
    {
        return $values['es'] !== '' && $values['pt'] !== '' && $values['en'] !== '';
    }

    protected function getFormActions(): array
    {
        if (! $this->canEditPuntos()) {
            return [];
        }

        return [
            \Filament\Actions\Action::make('save')
                ->label(__('common.actions.save'))
                ->submit('save'),
        ];
    }

    public function getTitle(): string
    {
        $user = auth()->user();
        if ($user?->isClientOwner()) {
            return __('client.survey.title.own');
        }

        return __('client.survey.title.record', ['client' => $this->getRecord()->namecommercial]);
    }

    /**
     * Datos para la vista de solo lectura (rol cliente): textos traducidos y lista de respuestas.
     *
     * @return array{default_locale: string, positive_scores: array<int, int>, positive_scores_label: string, survey_questions: array<string, string>, titles: array<string, string>, display_mode_label: string, options: array<int, array<string, string>>}
     */
    public function getPuntosReadOnlyData(): array
    {
        $client = $this->getRecord();
        $config = $client->improvementConfig;
        $options = $config ? $config->options()->orderBy('sort_order')->orderBy('created_at')->get() : collect();
        $mode = ClientImprovementConfig::normalizeDisplayMode($config?->display_mode);
        $defaultQuestions = ClientImprovementConfig::defaultSurveyQuestionTexts();
        $defaultTitles = ClientImprovementConfig::defaultTitles();

        return [
            'default_locale' => ClientImprovementConfig::normalizeDefaultLocale($config?->default_locale),
            'positive_scores' => $config?->positiveScores() ?? ClientImprovementConfig::defaultPositiveScores(),
            'positive_scores_label' => implode(', ', $config?->positiveScores() ?? ClientImprovementConfig::defaultPositiveScores()),
            'survey_questions' => [
                'es' => $config?->survey_question_text_es ?: $defaultQuestions['es'],
                'pt' => $config?->survey_question_text_pt ?: $defaultQuestions['pt'],
                'en' => $config?->survey_question_text_en ?: $defaultQuestions['en'],
            ],
            'titles' => [
                'es' => $config?->title_es ?: $defaultTitles['es'],
                'pt' => $config?->title_pt ?: $defaultTitles['pt'],
                'en' => $config?->title_en ?: $defaultTitles['en'],
            ],
            'display_mode_label' => $mode === ClientImprovementConfig::DISPLAY_MODE_FACES
                ? __('client.survey.display_modes.faces')
                : __('client.survey.display_modes.numbers'),
            'options' => $options->map(fn (ClientImprovementOption $option): array => [
                'es' => $option->label_es ?: $option->label,
                'pt' => $option->label_pt ?: ($option->label_es ?: $option->label),
                'en' => $option->label_en ?: ($option->label_es ?: $option->label),
            ])->values()->all(),
        ];
    }

    public function getBreadcrumb(): string
    {
        return __('client.survey.breadcrumb');
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
