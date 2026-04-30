<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\ClientImprovementConfig;
use App\Models\ClientImprovementOption;
use Filament\Pages\Page;

class ClientPuntosDeMejora extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.client-puntos-de-mejora';

    public static function getNavigationLabel(): string
    {
        return __('client.menu.survey');
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\MaxWidth|string|null
    {
        return \Filament\Support\Enums\MaxWidth::Full;
    }

    public function getTitle(): string
    {
        return __('client.survey.title.own');
    }

    public ?Client $client = null;

    public function mount(): void
    {
        $this->client = $this->resolveClient();
        if (! $this->client) {
            abort(404);
        }
    }

    protected function resolveClient(): ?Client
    {
        $user = auth()->user();
        if (! $user || ! $user->isClientOwner()) {
            return null;
        }

        return $user->ownedClient;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user?->isClientOwner() === true && $user->ownedClient !== null;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isClientOwner() === true && $user->ownedClient !== null;
    }

    /**
     * Datos para la vista de solo lectura del cliente.
     *
     * @return array{default_locale: string, positive_scores_label: string, survey_questions: array<string, string>, titles: array<string, string>, display_mode_label: string, options: array<int, array<string, string>>}
     */
    public function getSurveyReadOnlyData(): array
    {
        $config = $this->client?->improvementConfig;
        $options = $config
            ? $config->options()->orderBy('sort_order')->orderBy('created_at')->get()
            : collect();
        $mode = ClientImprovementConfig::normalizeDisplayMode($config?->display_mode);
        $defaultQuestions = ClientImprovementConfig::defaultSurveyQuestionTexts();
        $defaultTitles = ClientImprovementConfig::defaultTitles();
        $positiveScores = $config?->positiveScores() ?? ClientImprovementConfig::defaultPositiveScores();

        return [
            'default_locale' => ClientImprovementConfig::normalizeDefaultLocale($config?->default_locale),
            'positive_scores_label' => implode(', ', $positiveScores),
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
}
