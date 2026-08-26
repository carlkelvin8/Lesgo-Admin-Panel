<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function adjust(Wallet $wallet, float $amount, string $type, ?string $reason, int $adminId): WalletTransaction
    {
        if (! in_array($type, ['credit', 'debit'], true)) {
            throw ValidationException::withMessages(['amount' => 'Adjustment type must be credit or debit.']);
        }

        if ($type === 'debit' && $wallet->balance < $amount) {
            throw ValidationException::withMessages(['amount' => 'Insufficient wallet balance for this debit adjustment.']);
        }

        return DB::transaction(function () use ($wallet, $amount, $type, $reason, $adminId) {
            $lockedWallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $newBalance = $type === 'credit'
                ? round($lockedWallet->balance + $amount, 2)
                : round($lockedWallet->balance - $amount, 2);

            $lockedWallet->update(['balance' => $newBalance]);

            return WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => $type === 'credit' ? 'admin_credit' : 'admin_debit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $reason ?? "Admin {$type} adjustment",
                'reference_type' => 'admin_adjustment',
                'reference_id' => $adminId,
            ]);
        });
    }

    public function approveTopUp($topUp, int $adminId): void
    {
        DB::transaction(function () use ($topUp, $adminId) {
            $lockedTopUp = \App\Models\WalletTopUp::whereKey($topUp->id)->lockForUpdate()->firstOrFail();

            if ($lockedTopUp->status !== 'pending') {
                throw ValidationException::withMessages(['top_up' => 'This top-up has already been reviewed.']);
            }

            $lockedTopUp->update([
                'status' => 'approved',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]);

            $wallet = Wallet::findOrFail($lockedTopUp->wallet_id);
            $newBalance = round($wallet->balance + $lockedTopUp->amount, 2);
            $wallet->update(['balance' => $newBalance]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'top_up',
                'amount' => $lockedTopUp->amount,
                'balance_after' => $newBalance,
                'description' => 'Wallet top-up approved',
                'reference_type' => 'wallet_top_up',
                'reference_id' => $lockedTopUp->id,
            ]);
        });
    }
}
