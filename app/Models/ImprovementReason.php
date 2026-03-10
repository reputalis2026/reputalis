<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImprovementReason extends Model
{
    use HasFactory;

    protected $table = 'improvementreasons';

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relación con las encuestas CSAT que eligieron esta razón.
     */
    public function csatSurveys(): HasMany
    {
        return $this->hasMany(CsatSurvey::class, 'improvementreason_id');
    }
}
