<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientImprovementReasonLabel extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'client_improvement_reason_labels';

    protected $fillable = [
        'client_id',
        'improvement_reason_code',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'client_id' => 'string',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
