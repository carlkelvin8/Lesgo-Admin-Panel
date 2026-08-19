<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'channel',
        'read_at',
        'delivery_status',
        'delivery_attempts',
        'delivered_via',
        'delivery_reference',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
            'delivery_attempts' => 'integer',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
