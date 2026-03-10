<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    protected $fillable = ['name', 'sort_order'];

    public function clientsCount(): int
    {
        return Client::where('sector', $this->name)->count();
    }

    public function canDelete(): bool
    {
        return $this->clientsCount() === 0;
    }
}
