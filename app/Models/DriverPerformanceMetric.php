<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverPerformanceMetric extends Model
{
    protected $fillable = [
        'driver_id', 'date', 'total_orders', 'completed_orders', 'cancelled_orders',
        'total_revenue', 'total_distance_km', 'online_minutes', 'average_rating',
        'total_ratings', 'acceptance_rate', 'completion_rate', 'average_delivery_time',
        'customer_complaints', 'performance_data',
    ];

    protected $casts = [
        'date' => 'date',
        'total_revenue' => 'decimal:2',
        'total_distance_km' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'acceptance_rate' => 'decimal:2',
        'completion_rate' => 'decimal:2',
        'average_delivery_time' => 'decimal:2',
        'performance_data' => 'array',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
