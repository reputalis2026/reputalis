<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsatSurvey extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'employee_id',
        'score',
        'improvementreason_id',
        'improvement_option_id',
        'locale_used',
        'device_hash',
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
            'client_id' => 'string',
            'employee_id' => 'string',
            'improvementreason_id' => 'string',
            'improvement_option_id' => 'string',
        ];
    }

    /**
     * Relación con el cliente.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Relación con el empleado (opcional).
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Relación con la razón de mejora (solo cuando score 1–3).
     */
    public function improvementReason(): BelongsTo
    {
        return $this->belongsTo(ImprovementReason::class, 'improvementreason_id');
    }

    /**
     * Opción de mejora elegida por el usuario (encuesta negativa).
     */
    public function improvementOption(): BelongsTo
    {
        return $this->belongsTo(ClientImprovementOption::class, 'improvement_option_id');
    }
}
