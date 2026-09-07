<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MissionTemplate extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'target_audience',
        'goal_type',
        'goal_target',
        'reward_amount',
        'reward_currency',
        'service_code',
        'is_active',
    ];

    protected $casts = [
        'goal_target' => 'integer',
        'reward_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
