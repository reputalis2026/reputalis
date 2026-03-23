<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

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

    protected static function booted(): void
    {
        // En PostgreSQL el UUID puede venir como default en BD, pero Eloquent a veces
        // no lo devuelve en el modelo inmediatamente. Para mantener consistencia (y
        // para el enlace 1–1 con NfcToken) generamos el UUID en app si falta.
        static::creating(function (self $model): void {
            if (! filled($model->getAttribute('id'))) {
                $model->setAttribute('id', (string) Str::uuid());
            }
        });
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
