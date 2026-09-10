<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ClientExternalReputationSnapshot extends Model
{
    public const SOURCE_OUTSCRAPER = 'outscraper';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_SIMULATED = 'simulated';

    protected $table = 'client_external_reputation_snapshots';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'client_id',
        'captured_at',
        'snapshot_date',
        'rating',
        'reviews_total',
        'stars_1',
        'stars_2',
        'stars_3',
        'stars_4',
        'stars_5',
        'calculated_rating',
        'source',
        'raw_payload',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $snapshot): void {
            if (blank($snapshot->id)) {
                $snapshot->id = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'client_id' => 'string',
            'captured_at' => 'datetime',
            'snapshot_date' => 'date',
            'rating' => 'decimal:2',
            'reviews_total' => 'integer',
            'stars_1' => 'integer',
            'stars_2' => 'integer',
            'stars_3' => 'integer',
            'stars_4' => 'integer',
            'stars_5' => 'integer',
            'calculated_rating' => 'decimal:4',
            'raw_payload' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function alertsFrom(): HasMany
    {
        return $this->hasMany(ClientExternalReputationAlert::class, 'from_snapshot_id');
    }

    public function alertsTo(): HasMany
    {
        return $this->hasMany(ClientExternalReputationAlert::class, 'to_snapshot_id');
    }

    /**
     * @return array{1: int, 2: int, 3: int, 4: int, 5: int}
     */
    public function starsBreakdown(): array
    {
        return [
            1 => (int) $this->stars_1,
            2 => (int) $this->stars_2,
            3 => (int) $this->stars_3,
            4 => (int) $this->stars_4,
            5 => (int) $this->stars_5,
        ];
    }

    public function starsSum(): int
    {
        return array_sum($this->starsBreakdown());
    }

    /**
     * Media aritmética a partir del desglose: Σ(i × stars_i) / total.
     *
     * @param  array{1?: int, 2?: int, 3?: int, 4?: int, 5?: int}  $stars
     */
    public static function calculateRatingFromStars(array $stars): ?float
    {
        $s1 = (int) ($stars[1] ?? 0);
        $s2 = (int) ($stars[2] ?? 0);
        $s3 = (int) ($stars[3] ?? 0);
        $s4 = (int) ($stars[4] ?? 0);
        $s5 = (int) ($stars[5] ?? 0);
        $total = $s1 + $s2 + $s3 + $s4 + $s5;

        if ($total <= 0) {
            return null;
        }

        return round((1 * $s1 + 2 * $s2 + 3 * $s3 + 4 * $s4 + 5 * $s5) / $total, 4);
    }

    /**
     * Estimación: mínimo de nuevas reseñas de 5★ para alcanzar (o superar) el objetivo.
     *
     * @param  array{1?: int, 2?: int, 3?: int, 4?: int, 5?: int}  $stars
     */
    public static function fiveStarsNeededForTarget(array $stars, float $targetRating): ?int
    {
        if ($targetRating <= 0 || $targetRating > 5) {
            return null;
        }

        $s1 = (int) ($stars[1] ?? 0);
        $s2 = (int) ($stars[2] ?? 0);
        $s3 = (int) ($stars[3] ?? 0);
        $s4 = (int) ($stars[4] ?? 0);
        $s5 = (int) ($stars[5] ?? 0);
        $total = $s1 + $s2 + $s3 + $s4 + $s5;
        $weighted = 1 * $s1 + 2 * $s2 + 3 * $s3 + 4 * $s4 + 5 * $s5;

        if ($total <= 0) {
            return $targetRating <= 5 ? 1 : null;
        }

        $current = $weighted / $total;
        if ($current >= $targetRating) {
            return 0;
        }

        // (weighted + 5n) / (total + n) >= target  =>  n >= (target*total - weighted) / (5 - target)
        if ($targetRating >= 5) {
            return null;
        }

        $n = (int) ceil(($targetRating * $total - $weighted) / (5 - $targetRating));

        return max(0, $n);
    }
}
