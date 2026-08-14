<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientImprovementConfig extends Model
{
    public const DISPLAY_MODE_NUMBERS = 'numbers';

    public const DISPLAY_MODE_FACES = 'faces';

    public const DEFAULT_LOCALE = 'es';

    public const SUPPORTED_LOCALES = ['es', 'pt', 'en'];

    public const DEFAULT_POSITIVE_SCORES = [4, 5];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'client_improvement_configs';

    protected $fillable = [
        'id',
        'client_id',
        'default_locale',
        'title',
        'title_es',
        'title_pt',
        'title_en',
        'display_mode',
        'survey_question_text',
        'survey_question_text_es',
        'survey_question_text_pt',
        'survey_question_text_en',
        'positive_scores',
        'google_place_id',
        'google_review_message',
        'google_review_message_es',
        'google_review_message_pt',
        'google_review_message_en',
    ];

    public const GOOGLE_PLACE_ID_FINDER_URL = 'https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder';

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'client_id' => 'string',
            'default_locale' => 'string',
            'survey_question_text' => 'string',
            'survey_question_text_es' => 'string',
            'survey_question_text_pt' => 'string',
            'survey_question_text_en' => 'string',
            'positive_scores' => 'array',
            'google_place_id' => 'string',
            'google_review_message' => 'string',
            'google_review_message_es' => 'string',
            'google_review_message_pt' => 'string',
            'google_review_message_en' => 'string',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ClientImprovementOption::class, 'client_improvement_config_id')
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    public function activeOptions(): HasMany
    {
        return $this->options()->where('is_active', true);
    }

    /**
     * @return self::DISPLAY_MODE_NUMBERS|self::DISPLAY_MODE_FACES
     */
    public static function normalizeDisplayMode(?string $value): string
    {
        return in_array($value, [self::DISPLAY_MODE_NUMBERS, self::DISPLAY_MODE_FACES], true)
            ? $value
            : self::DISPLAY_MODE_NUMBERS;
    }

    public static function normalizeLocale(?string $value): ?string
    {
        $locale = strtolower(trim((string) $value));
        if ($locale === '') {
            return null;
        }

        $locale = str_replace('_', '-', $locale);
        $locale = explode('-', $locale)[0] ?? null;

        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : null;
    }

    public static function normalizeDefaultLocale(?string $value): string
    {
        return self::normalizeLocale($value) ?? self::DEFAULT_LOCALE;
    }

    /**
     * @return array<string, string>
     */
    public static function defaultSurveyQuestionTexts(): array
    {
        return [
            'es' => '¿Cómo le hemos atendido hoy?',
            'pt' => 'Como fomos no seu atendimento hoje?',
            'en' => 'How was your experience today?',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultTitles(): array
    {
        return [
            'es' => '¿En qué podemos mejorar?',
            'pt' => 'Em que podemos melhorar?',
            'en' => 'What can we improve?',
        ];
    }

    /**
     * @return array<int, int>
     */
    public static function defaultPositiveScores(): array
    {
        return self::DEFAULT_POSITIVE_SCORES;
    }

    /**
     * @return array<int, int>
     */
    public static function normalizePositiveScores(mixed $scores): array
    {
        if (! is_array($scores)) {
            return [];
        }

        $normalized = array_values(array_unique(array_filter(array_map(
            fn (mixed $score): int => (int) $score,
            $scores
        ), fn (int $score): bool => $score >= 1 && $score <= 5)));

        sort($normalized);

        return $normalized;
    }

    public static function positiveScoresAreValid(array $scores): bool
    {
        $scores = self::normalizePositiveScores($scores);
        if (count($scores) === 0 || count($scores) === 5) {
            return false;
        }

        $expected = range(min($scores), 5);

        return $scores === $expected;
    }

    /**
     * @return array<int, int>
     */
    public function positiveScores(): array
    {
        $scores = self::normalizePositiveScores($this->positive_scores);

        return self::positiveScoresAreValid($scores) ? $scores : self::defaultPositiveScores();
    }

    public function isPositiveScore(int $score): bool
    {
        return in_array($score, $this->positiveScores(), true);
    }

    public function surveyQuestionTextForLocale(?string $locale): string
    {
        return $this->localizedValue('survey_question_text', $locale)
            ?? self::defaultSurveyQuestionTexts()[self::DEFAULT_LOCALE];
    }

    public function titleForLocale(?string $locale): string
    {
        return $this->localizedValue('title', $locale)
            ?? self::defaultTitles()[self::DEFAULT_LOCALE];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultGoogleReviewMessages(): array
    {
        return [
            'es' => 'Le agradeceríamos que deje una reseña en Google Maps.',
            'pt' => 'Agradecíamos que deixasse uma avaliação no Google Maps.',
            'en' => 'We would appreciate it if you leave a review on Google Maps.',
        ];
    }

    public function googleReviewMessageForLocale(?string $locale): string
    {
        return $this->localizedValue('google_review_message', $locale)
            ?? self::defaultGoogleReviewMessages()[self::DEFAULT_LOCALE];
    }

    public static function normalizeGooglePlaceId(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/[?&]placeid=([^&]+)/i', $value, $matches) === 1) {
            $value = urldecode($matches[1]);
        } elseif (preg_match('/place_id:([A-Za-z0-9_-]+)/', $value, $matches) === 1) {
            $value = $matches[1];
        }

        $value = trim($value);
        if ($value === '' || ! preg_match('/^[A-Za-z0-9_-]{10,255}$/', $value)) {
            return null;
        }

        return $value;
    }

    public function googleReviewUrl(): ?string
    {
        $this->loadMissing('client');

        // Fuente preferente: Place ID en ficha del cliente; fallback a columna legacy de encuesta.
        $placeId = self::normalizeGooglePlaceId($this->client?->google_place_id)
            ?? self::normalizeGooglePlaceId($this->google_place_id);

        return $placeId !== null
            ? 'https://search.google.com/local/writereview?placeid=' . rawurlencode($placeId)
            : null;
    }

    public function hasTextForLocale(?string $locale): bool
    {
        $locale = self::normalizeLocale($locale);
        if (! $locale) {
            return false;
        }

        return filled($this->getAttribute("survey_question_text_{$locale}"))
            && filled($this->getAttribute("title_{$locale}"));
    }

    private function localizedValue(string $baseAttribute, ?string $locale): ?string
    {
        $locale = self::normalizeLocale($locale) ?? self::DEFAULT_LOCALE;
        $value = trim((string) ($this->getAttribute("{$baseAttribute}_{$locale}") ?? ''));
        if ($value !== '') {
            return $value;
        }

        $defaultLocale = self::normalizeDefaultLocale($this->default_locale);
        if ($defaultLocale !== $locale) {
            $defaultValue = trim((string) ($this->getAttribute("{$baseAttribute}_{$defaultLocale}") ?? ''));
            if ($defaultValue !== '') {
                return $defaultValue;
            }
        }

        $spanishValue = trim((string) ($this->getAttribute("{$baseAttribute}_es") ?? ''));
        if ($spanishValue !== '') {
            return $spanishValue;
        }

        $legacyValue = trim((string) ($this->getAttribute($baseAttribute) ?? ''));

        return $legacyValue !== '' ? $legacyValue : null;
    }
}
