<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event_type', 'event_category', 'action', 'resource_type',
        'resource_id', 'old_values', 'new_values', 'ip_address', 'user_agent',
        'session_id', 'request_id', 'risk_level', 'is_suspicious', 'context',
        'metadata', 'occurred_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'context' => 'array',
        'metadata' => 'array',
        'is_suspicious' => 'boolean',
        'occurred_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
