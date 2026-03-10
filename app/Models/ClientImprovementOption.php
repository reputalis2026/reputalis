<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientImprovementOption extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'client_improvement_options';

    protected $fillable = [
        'client_improvement_config_id',
        'label',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'client_improvement_config_id' => 'string',
        ];
    }

    public function clientImprovementConfig(): BelongsTo
    {
        return $this->belongsTo(ClientImprovementConfig::class, 'client_improvement_config_id');
    }
}
