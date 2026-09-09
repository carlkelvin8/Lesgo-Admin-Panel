<?php

namespace App\Services;

use App\Models\RegistrationFeePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegistrationFeeService
{
    public static function approveApplication(RegistrationFeePayment $payment, User $admin): RegistrationFeePayment
    {
        return DB::transaction(function () use ($payment, $admin) {
            $locked = RegistrationFeePayment::where('id', $payment->id)->lockForUpdate()->first();
            $locked->update([
                'application_status' => RegistrationFeePayment::APP_APPROVED,
                'approved_at' => now(),
                'approved_by' => $admin->id,
            ]);
            return self::syncActivation($locked->fresh());
        });
    }

    public static function rejectApplication(RegistrationFeePayment $payment, User $admin, ?string $reason = null): RegistrationFeePayment
    {
        return DB::transaction(function () use ($payment, $admin, $reason) {
            $locked = RegistrationFeePayment::where('id', $payment->id)->lockForUpdate()->first();
            $locked->update([
                'application_status' => RegistrationFeePayment::APP_REJECTED,
                'approved_at' => null,
                'approved_by' => $admin->id,
                'is_active' => false,
                'activated_at' => null,
                'failure_reason' => $reason,
            ]);
            if ($locked->account_type === 'rider') {
                \App\Models\DriverProfile::where('user_id', $locked->user_id)->update(['status' => 'suspended']);
            } else {
                \App\Models\Partner::where('user_id', $locked->user_id)->update(['status' => 'rejected']);
            }
            return $locked->fresh();
        });
    }

    public static function waiveFee(RegistrationFeePayment $payment, User $admin, ?string $reason = null): RegistrationFeePayment
    {
        return DB::transaction(function () use ($payment, $admin, $reason) {
            $locked = RegistrationFeePayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->isPaid()) {
                throw new \DomainException('A paid registration fee cannot be changed to waived.');
            }
            if (!$locked->isWaived()) {
                $locked->update([
                    'payment_status' => RegistrationFeePayment::PAYMENT_WAIVED,
                    'waived_at' => now(),
                    'waived_by' => $admin->id,
                    'waiver_reason' => $reason,
                    'failure_reason' => null,
                    'metadata' => array_merge($locked->metadata ?? [], [
                        'fee_resolution' => 'admin_waiver',
                        'waiver_audit' => ['waived_at' => now()->toIso8601String(), 'waived_by' => $admin->id, 'reason' => $reason],
                    ]),
                ]);
            }
            return self::syncActivation($locked->fresh());
        });
    }

    /**
     * Resolve a pending application from the admin UI in one action.
     *
     * A waiver by itself does not activate an account because activation also
     * requires application approval. Keeping both updates in one transaction
     * prevents a rider from being left in the misleading pending/waived state.
     */
    public static function waiveAndApprove(RegistrationFeePayment $payment, User $admin, ?string $reason = null): RegistrationFeePayment
    {
        return DB::transaction(function () use ($payment, $admin, $reason) {
            $locked = RegistrationFeePayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                throw new \DomainException('A paid registration fee cannot be changed to waived.');
            }

            if (!$locked->isWaived()) {
                $locked->update([
                    'payment_status' => RegistrationFeePayment::PAYMENT_WAIVED,
                    'waived_at' => now(),
                    'waived_by' => $admin->id,
                    'waiver_reason' => $reason,
                    'failure_reason' => null,
                    'metadata' => array_merge($locked->metadata ?? [], [
                        'fee_resolution' => 'admin_waiver',
                        'waiver_audit' => [
                            'waived_at' => now()->toIso8601String(),
                            'waived_by' => $admin->id,
                            'reason' => $reason,
                        ],
                    ]),
                ]);
            }

            if (!$locked->isApproved()) {
                $locked->update([
                    'application_status' => RegistrationFeePayment::APP_APPROVED,
                    'approved_at' => now(),
                    'approved_by' => $admin->id,
                ]);
            }

            return self::syncActivation($locked->fresh());
        });
    }

    public static function syncActivation(RegistrationFeePayment $payment): RegistrationFeePayment
    {
        $isActive = $payment->shouldBeActive();
        $payment->update([
            'is_active' => $isActive,
            'activated_at' => $isActive ? ($payment->activated_at ?? now()) : null,
        ]);

        if ($payment->account_type === 'rider') {
            $status = $isActive ? 'active' : ($payment->application_status === RegistrationFeePayment::APP_REJECTED ? 'suspended' : 'pending');
            \App\Models\DriverProfile::where('user_id', $payment->user_id)->update(['status' => $status]);
        } else {
            $status = $isActive ? 'active' : ($payment->application_status === RegistrationFeePayment::APP_REJECTED ? 'rejected' : 'pending');
            \App\Models\Partner::where('user_id', $payment->user_id)->update(['status' => $status]);
        }

        return $payment->fresh();
    }
}
