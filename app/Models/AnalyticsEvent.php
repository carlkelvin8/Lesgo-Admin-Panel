<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    protected $fillable = [
        'user_id', 'event_type', 'event_category', 'event_action', 'event_label',
        'event_value', 'properties', 'session_id', 'device_type', 'platform',
        'app_version', 'ip_address', 'user_agent', 'latitude', 'longitude', 'event_time',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'properties' => 'array',
    ];
}
