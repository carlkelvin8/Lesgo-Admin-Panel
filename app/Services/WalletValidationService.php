<?php

namespace App\Services;

use App\Models\SecuritySetting;

class WalletValidationService
{
    public const THRESHOLD_SETTING_KEY = 'wallet_minimum_balance_threshold';
    public const DEFAULT_THRESHOLD = 50.00;

    public static function getMinimumThreshold(): float
    {
        return (float) SecuritySetting::value(
            static::THRESHOLD_SETTING_KEY,
            static::DEFAULT_THRESHOLD
        );
    }

    public static function hasInsufficientBalance(float $balance): bool
    {
        return $balance < static::getMinimumThreshold();
    }

    public static function getBalanceDeficit(float $balance): float
    {
        $threshold = static::getMinimumThreshold();

        return max(0.0, $threshold - $balance);
    }
}
