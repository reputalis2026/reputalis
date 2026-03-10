<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientImprovementConfig extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'client_improvement_configs';

    protected $fillable = [
        'id',
        'client_id',
        'title',
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

    public function options(): HasMany
    {
        return $this->hasMany(ClientImprovementOption::class, 'client_improvement_config_id')
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }
}
