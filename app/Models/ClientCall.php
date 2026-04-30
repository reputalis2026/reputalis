<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ClientCall extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (ClientCall $call): void {
            $calledAt = $call->called_at ?? now();
            if (! $calledAt instanceof Carbon) {
                $calledAt = Carbon::parse((string) $calledAt);
            }

            $client = $call->client()->first();
            if (! $client) {
                return;
            }

            $client->update([
                'last_call_at' => $calledAt,
                'next_call_at' => $calledAt->copy()->addDays(30),
            ]);
        });
    }

    protected $table = 'client_calls';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'client_id',
        'called_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'client_id' => 'string',
            'called_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}

