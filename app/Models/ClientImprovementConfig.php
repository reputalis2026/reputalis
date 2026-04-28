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
    ];

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
