<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'title',
        'description',
        'discount_text',
        'min_order',
        'type',
        'value',
        'max_discount',
        'min_order_value',
        'max_uses',
        'expires_at',
        'user_restrictions',
        'applicable_services',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_order_value' => 'decimal:2',
        'expires_at' => 'datetime',
        'user_restrictions' => 'array',
        'applicable_services' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function ($voucher) {
            $voucher->code = strtoupper(trim($voucher->code));
        });
    }
}
