<?php

namespace Tests\Unit\Models;

use App\Models\TwoFactorAuth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    public function testGenerateTotpSecretCreatesSecret(): void
    {
        $user = User::factory()->create();
        $tfa = TwoFactorAuth::factory()->create(['user_id' => $user->id, 'secret' => null]);

        $tfa->generateTotpSecret();

        $this->assertNotNull($tfa->fresh()->secret);
    }

    public function testVerifyCodeReturnsTrueForValidCode(): void
    {
        $user = User::factory()->create();
        $tfa = TwoFactorAuth::factory()->create([
            'user_id' => $user->id,
            'method' => 'totp',
            'is_enabled' => true,
        ]);

        $plainSecret = $tfa->generateTotpSecret();

        $mock = $this->mock(\PragmaRX\Google2FA\Google2FA::class, function ($mock) {
            $mock->shouldReceive('verifyKey')->once()->andReturn(true);
        });

        $this->app->instance(\PragmaRX\Google2FA\Google2FA::class, $mock);

        $this->assertTrue($tfa->verifyCode('123456'));
    }

    public function testGenerateBackupCodesReturns10Codes(): void
    {
        $user = User::factory()->create();
        $tfa = TwoFactorAuth::factory()->create(['user_id' => $user->id]);

        $codes = $tfa->generateBackupCodes();

        $this->assertCount(10, $codes);
    }

    public function testUseBackupCodeReturnsTrueForValidCode(): void
    {
        $user = User::factory()->create();
        $tfa = TwoFactorAuth::factory()->create(['user_id' => $user->id]);
        $codes = $tfa->generateBackupCodes();
        $codeToUse = $codes[0];

        $this->assertTrue($tfa->useBackupCode($codeToUse));
    }

    public function testUseBackupCodeReturnsFalseForInvalidCode(): void
    {
        $user = User::factory()->create();
        $tfa = TwoFactorAuth::factory()->create(['user_id' => $user->id]);
        $tfa->generateBackupCodes();

        $this->assertFalse($tfa->useBackupCode('nonexistent-code'));
    }

    public function testIsEnabledReturnsTrueWhenEnabled(): void
    {
        $user = User::factory()->create();
        $tfa = TwoFactorAuth::factory()->create([
            'user_id' => $user->id,
            'is_enabled' => true,
            'method' => 'totp',
        ]);

        $this->assertTrue($tfa->isEnabled());
    }
}
