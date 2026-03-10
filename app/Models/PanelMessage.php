<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PanelMessage extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'sender_user_id',
        'client_id',
        'title',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'sender_user_id' => 'string',
            'client_id' => 'string',
        ];
    }

    public const TYPE_CLIENT_PENDING_ACTIVATION = 'client_pending_activation';
    public const TYPE_CLIENT_ACTIVATED = 'client_activated';

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(PanelMessageRecipient::class, 'panel_message_id');
    }
}
