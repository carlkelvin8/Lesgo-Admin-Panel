<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueAnalytics extends Model
{
    protected $fillable = [
        'date', 'revenue_type', 'revenue_source', 'service_id', 'partner_id',
        'amount', 'currency', 'transaction_count', 'average_transaction_value', 'breakdown',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'average_transaction_value' => 'decimal:2',
        'breakdown' => 'array',
    ];
}
