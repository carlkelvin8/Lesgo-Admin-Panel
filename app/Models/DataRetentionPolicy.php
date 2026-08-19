<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataRetentionPolicy extends Model
{
    protected $fillable = [
        'data_type', 'category', 'retention_days', 'deletion_method',
        'is_active', 'description', 'conditions', 'metadata',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'conditions' => 'array', 'metadata' => 'array'];
    }
}
