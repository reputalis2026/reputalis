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

        static::saving(function (Employee $employee): void {
            if (is_string($employee->name)) {
                $employee->name = trim($employee->name);
            }
        });

        static::saved(function (Employee $employee): void {
            if (! $employee->wasChanged('is_active')) {
                return;
            }

            $employee->nfcTokens()->update([
                'is_active' => (bool) $employee->is_active,
            ]);
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

    public static function resolveForClientSurvey(string $clientId, ?string $employeeId, ?string $employeeCode): ?self
    {
        if (filled($employeeId)) {
            return static::query()
                ->where('client_id', $clientId)
                ->whereKey($employeeId)
                ->where('is_active', true)
                ->first();
        }

        if (! filled($employeeCode)) {
            return null;
        }

        $normalizedCode = trim($employeeCode);

        return static::query()
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->where(function ($query) use ($employeeCode, $normalizedCode): void {
                $query->where('name', $employeeCode)
                    ->orWhere('name', $normalizedCode)
                    ->orWhereRaw('TRIM(name) = ?', [$normalizedCode]);
            })
            ->first();
    }

    public static function inactiveMatchForClientSurvey(string $clientId, ?string $employeeId, ?string $employeeCode): bool
    {
        if (filled($employeeId)) {
            return static::query()
                ->where('client_id', $clientId)
                ->whereKey($employeeId)
                ->where('is_active', false)
                ->exists();
        }

        if (! filled($employeeCode)) {
            return false;
        }

        $normalizedCode = trim($employeeCode);

        return static::query()
            ->where('client_id', $clientId)
            ->where('is_active', false)
            ->where(function ($query) use ($employeeCode, $normalizedCode): void {
                $query->where('name', $employeeCode)
                    ->orWhere('name', $normalizedCode)
                    ->orWhereRaw('TRIM(name) = ?', [$normalizedCode]);
            })
            ->exists();
    }
}
