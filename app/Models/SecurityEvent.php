<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    protected $fillable = [
        'user_id', 'event_type', 'severity', 'source', 'ip_address', 'user_agent',
        'description', 'event_data', 'is_resolved', 'resolved_at', 'resolved_by',
        'resolution_notes', 'metadata', 'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'event_data' => 'array',
            'metadata' => 'array',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'detected_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
