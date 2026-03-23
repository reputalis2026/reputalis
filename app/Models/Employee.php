<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
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
        'name',
        'alias',
        'photo',
        'position',
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
            'client_id' => 'string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relación con el cliente del empleado.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Relación con las encuestas CSAT del empleado.
     */
    public function csatSurveys(): HasMany
    {
        return $this->hasMany(CsatSurvey::class, 'employee_id');
    }

    /**
     * Relación con los tokens NFC asignados al empleado.
     */
    public function nfcTokens(): HasOne
    {
        // Regla del dominio: cada empleado tiene exactamente un token NFC lógico.
        return $this->hasOne(NfcToken::class, 'employee_id');
    }
}
