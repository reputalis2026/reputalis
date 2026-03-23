<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCall extends Model
{
    use HasFactory;

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

