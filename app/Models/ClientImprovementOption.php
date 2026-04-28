<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientImprovementOption extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'client_improvement_options';

    protected $fillable = [
        'id',
        'client_improvement_config_id',
        'label',
        'label_es',
        'label_pt',
        'label_en',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'client_improvement_config_id' => 'string',
        ];
    }

    public function clientImprovementConfig(): BelongsTo
    {
        return $this->belongsTo(ClientImprovementConfig::class, 'client_improvement_config_id');
    }

    /**
     * @return array<int, array{es: string, pt: string, en: string}>
     */
    public static function defaultLabels(): array
    {
        return [
            [
                'es' => 'Tiempo de espera',
                'pt' => 'Tempo de espera',
                'en' => 'Waiting time',
            ],
            [
                'es' => 'Atención recibida',
                'pt' => 'Atendimento recebido',
                'en' => 'Service received',
            ],
        ];
    }

    public function labelForLocale(?string $locale): string
    {
        $locale = ClientImprovementConfig::normalizeLocale($locale) ?? ClientImprovementConfig::DEFAULT_LOCALE;
        $value = trim((string) ($this->getAttribute("label_{$locale}") ?? ''));
        if ($value !== '') {
            return $value;
        }

        $spanishValue = trim((string) ($this->label_es ?? ''));
        if ($spanishValue !== '') {
            return $spanishValue;
        }

        $legacyValue = trim((string) ($this->label ?? ''));

        return $legacyValue !== '' ? $legacyValue : '';
    }

    public function hasLabelForLocale(?string $locale): bool
    {
        $locale = ClientImprovementConfig::normalizeLocale($locale);

        return $locale !== null && filled($this->getAttribute("label_{$locale}"));
    }
}
