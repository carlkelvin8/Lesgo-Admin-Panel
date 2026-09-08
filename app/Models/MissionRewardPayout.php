<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionRewardPayout extends Model
{
    protected $table = 'mission_reward_payouts';

    protected $fillable = [
        'rider_user_id',
        'driver_profile_id',
        'mission_id',
        'mission_template_id',
        'reward_amount',
        'reward_currency',
        'paymongo_transfer_id',
        'paymongo_reference',
        'idempotency_key',
        'provider',
        'status',
        'failure_reason',
        'attempts',
        'paymongo_payload',
        'paymongo_response',
        'requested_at',
        'processed_at',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'attempts' => 'integer',
        'paymongo_payload' => 'array',
        'paymongo_response' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_user_id');
    }

    public function missionTemplate(): BelongsTo
    {
        return $this->belongsTo(MissionTemplate::class, 'mission_template_id');
    }

    public function mission(): BelongsTo
    {
        // DriverMission may not have model in admin panel; use generic
        return $this->belongsTo(DriverMission::class, 'mission_id');
    }

    public function driverProfile(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class, 'driver_profile_id');
    }
}
