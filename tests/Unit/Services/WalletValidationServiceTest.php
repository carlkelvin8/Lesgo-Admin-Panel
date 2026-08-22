<?php

namespace Tests\Unit\Services;

use App\Models\SecuritySetting;
use App\Models\Wallet;
use App\Services\WalletValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testGetMinimumThresholdReturnsDefault(): void
    {
        $this->assertEquals(50.00, WalletValidationService::getMinimumThreshold());
    }

    public function testGetMinimumThresholdReturnsConfiguredValue(): void
    {
        SecuritySetting::create([
            'setting_key' => 'wallet_minimum_balance_threshold',
            'setting_value' => '100.00',
            'data_type' => 'string',
            'category' => 'wallet',
        ]);

        $this->assertEquals(100.00, WalletValidationService::getMinimumThreshold());
    }

    public function testHasInsufficientBalanceReturnsTrueWhenBelowThreshold(): void
    {
        $this->assertTrue(WalletValidationService::hasInsufficientBalance(10));
    }

    public function testHasInsufficientBalanceReturnsFalseWhenAboveThreshold(): void
    {
        $this->assertFalse(WalletValidationService::hasInsufficientBalance(100));
    }

    public function testGetBalanceDeficitReturnsCorrectValue(): void
    {
        $this->assertEquals(20.0, WalletValidationService::getBalanceDeficit(30));
    }

    public function testGetBalanceDeficitReturnsZeroWhenAboveThreshold(): void
    {
        $this->assertEquals(0.0, WalletValidationService::getBalanceDeficit(100));
    }
}
