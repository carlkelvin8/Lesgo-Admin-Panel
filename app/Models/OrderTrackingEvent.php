<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTrackingEvent extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'event_type', 'event_title', 'event_description',
        'event_category', 'latitude', 'longitude', 'location_address', 'metadata',
        'attachments', 'is_visible_to_customer', 'is_milestone', 'event_time',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'attachments' => 'array',
            'is_visible_to_customer' => 'boolean',
            'is_milestone' => 'boolean',
            'event_time' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
