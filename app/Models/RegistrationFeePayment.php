<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationFeePayment extends Model
{
    protected $table = 'registration_fee_payments';

    protected $fillable = [
        'user_id',
        'account_type',
        'amount',
        'currency',
        'paymongo_checkout_id',
        'paymongo_reference',
        'idempotency_key',
        'checkout_url',
        'payment_status',
        'payment_date',
        'application_status',
        'approved_at',
        'approved_by',
        'activated_at',
        'is_active',
        'is_grandfathered',
        'failure_reason',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'approved_at' => 'datetime',
        'activated_at' => 'datetime',
        'is_active' => 'boolean',
        'is_grandfathered' => 'boolean',
        'metadata' => 'array',
    ];

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_EXPIRED = 'expired';

    public const APP_PENDING = 'pending';
    public const APP_APPROVED = 'approved';
    public const APP_REJECTED = 'rejected';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
