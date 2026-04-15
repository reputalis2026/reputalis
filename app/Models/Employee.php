<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        // Con UUID generado en BD por defecto, Eloquent no asigna $model->id tras insert;
        // sin id en memoria, las relaciones (p. ej. nfcTokens()->create()) dejan employee_id nulo.
        static::creating(function (Employee $employee): void {
            if (! $employee->getKey()) {
                $employee->setAttribute($employee->getKeyName(), (string) Str::uuid());
            }
        });
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'client_id',
        'name',
        'alias',
        'photo',
        'position',
        'is_active',
    ];

    /**
     * @return array<string, string|mixed>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'client_id' => 'string',
            'is_active' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function nfcTokens(): HasOne
    {
        return $this->hasOne(NfcToken::class, 'employee_id');
    }
}
