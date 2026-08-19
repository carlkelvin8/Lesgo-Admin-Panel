<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'partner_id',
        'driver_id',
        'amount',
        'currency',
        'method',
        'status',
        'provider',
        'provider_reference',
        'paid_at',
        'meta',
        'refund_status',
        'refunded_amount',
        'refund_reason',
        'refunded_at',
        'reconciliation_status',
        'reconciled_at',
        'reconciled_by',
        'reconciliation_notes',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'meta' => 'array',
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'refunded_at' => 'datetime',
            'reconciled_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function reconciler()
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
