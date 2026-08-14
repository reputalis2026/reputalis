<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ClientExternalReputationAlert extends Model
{
    public const KIND_NEGATIVE_INCREASE = 'negative_increase';

    public const KIND_TOTAL_DROP = 'total_drop';

    protected $table = 'client_external_reputation_alerts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'client_id',
        'detected_at',
        'kind',
        'delta_stars_1',
        'delta_stars_2',
        'delta_reviews_total',
        'from_snapshot_id',
        'to_snapshot_id',
        'read_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $alert): void {
            if (blank($alert->id)) {
                $alert->id = (string) Str::uuid();
            }
            if (blank($alert->kind)) {
                $alert->kind = self::KIND_NEGATIVE_INCREASE;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'client_id' => 'string',
            'detected_at' => 'datetime',
            'delta_stars_1' => 'integer',
            'delta_stars_2' => 'integer',
            'delta_reviews_total' => 'integer',
            'from_snapshot_id' => 'string',
            'to_snapshot_id' => 'string',
            'read_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function fromSnapshot(): BelongsTo
    {
        return $this->belongsTo(ClientExternalReputationSnapshot::class, 'from_snapshot_id');
    }

    public function toSnapshot(): BelongsTo
    {
        return $this->belongsTo(ClientExternalReputationSnapshot::class, 'to_snapshot_id');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function isNegativeIncrease(): bool
    {
        return $this->kind === self::KIND_NEGATIVE_INCREASE
            || ($this->kind === null && ((int) $this->delta_stars_1 > 0 || (int) $this->delta_stars_2 > 0));
    }

    public function isTotalDropAnomaly(): bool
    {
        return $this->kind === self::KIND_TOTAL_DROP;
    }

    public function markAsRead(): void
    {
        if ($this->read_at !== null) {
            return;
        }

        $this->forceFill(['read_at' => now()])->save();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
