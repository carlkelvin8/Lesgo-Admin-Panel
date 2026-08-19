<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateLimitRule extends Model
{
    protected $fillable = [
        'name', 'endpoint_pattern', 'method', 'max_attempts', 'window_minutes',
        'scope', 'is_active', 'priority', 'conditions', 'metadata',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'conditions' => 'array', 'metadata' => 'array'];
    }
}
