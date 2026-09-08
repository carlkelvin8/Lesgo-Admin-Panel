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
            // is_active = paid + approved
            $isActive = $locked->fresh()->payment_status === RegistrationFeePayment::PAYMENT_PAID
                && $locked->fresh()->application_status === RegistrationFeePayment::APP_APPROVED;
            $locked->update([
                'is_active' => $isActive,
                'activated_at' => $isActive ? ($locked->activated_at ?? now()) : null,
            ]);
            // Sync partner/driver status if active
            if ($isActive) {
                if ($locked->account_type === 'rider') {
                    \App\Models\DriverProfile::where('user_id', $locked->user_id)->update(['status' => 'active']);
                } else {
                    \App\Models\Partner::where('user_id', $locked->user_id)->update(['status' => 'active']);
                }
            }
            return $locked->fresh();
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
}
