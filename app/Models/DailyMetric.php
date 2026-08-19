<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyMetric extends Model
{
    protected $fillable = [
        'date', 'metric_type', 'metric_category', 'metric_key', 'metric_value', 'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'metric_value' => 'decimal:2',
        'metadata' => 'array',
    ];
}
