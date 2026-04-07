<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientImprovementConfig extends Model
{
    public const DISPLAY_MODE_NUMBERS = 'numbers';

    public const DISPLAY_MODE_FACES = 'faces';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'client_improvement_configs';

    protected $fillable = [
        'id',
        'client_id',
        'title',
        'display_mode',
        'survey_question_text',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'client_id' => 'string',
            'survey_question_text' => 'string',
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
}
