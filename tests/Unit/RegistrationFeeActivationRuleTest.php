<?php

namespace Tests\Unit;

use App\Models\RegistrationFeePayment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RegistrationFeeActivationRuleTest extends TestCase
{
    public static function activationMatrix(): array
    {
        $cases = [];
        foreach (['rider', 'merchant'] as $type) {
            foreach ([
                ['pending', 'unpaid', false], ['pending', 'paid', false], ['pending', 'waived', false],
                ['approved', 'unpaid', false], ['approved', 'paid', true], ['approved', 'waived', true],
                ['rejected', 'paid', false], ['rejected', 'waived', false],
            ] as [$application, $fee, $expected]) {
                $cases["{$type}:{$application}:{$fee}"] = [$type, $application, $fee, $expected];
            }
        }
        return $cases;
    }

    #[DataProvider('activationMatrix')]
    public function test_activation_matrix(string $type, string $application, string $fee, bool $expected): void
    {
        $payment = new RegistrationFeePayment([
            'account_type' => $type,
            'application_status' => $application,
            'payment_status' => $fee,
        ]);
        self::assertSame($expected, $payment->shouldBeActive());
    }
}
