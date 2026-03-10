<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelMessageRecipient extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'panel_message_id',
        'user_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'panel_message_id' => 'string',
            'user_id' => 'string',
            'read_at' => 'datetime',
        ];
    }

    public function panelMessage(): BelongsTo
    {
        return $this->belongsTo(PanelMessage::class, 'panel_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}
