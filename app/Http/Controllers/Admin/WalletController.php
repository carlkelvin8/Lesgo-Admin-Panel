<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTopUp;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $query = Wallet::with(['user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $wallets = $query->latest()->paginate(20)->withQueryString();

        return view('admin.wallets.index', compact('wallets'));
    }

    public function show(Wallet $wallet)
    {
        $wallet->load(['user']);
        $recentTransactions = $wallet->transactions()->latest()->limit(20)->get();
        $recentTopUps = $wallet->topUps()->latest()->limit(20)->get();

        return view('admin.wallets.show', compact('wallet', 'recentTransactions', 'recentTopUps'));
    }

    public function topUps(Request $request)
    {
        $query = WalletTopUp::with(['user', 'wallet', 'reviewer']);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('external_id', 'like', "%{$search}%")
                    ->orWhere('gateway_reference', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        $topUps = $query->latest()->paginate(20)->withQueryString();

        return view('admin.wallets.top-ups', compact('topUps'));
    }

    public function adjust(Request $request, Wallet $wallet)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($wallet, $validated, $request) {
            $lockedWallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $before = (float) $lockedWallet->balance;
            $amount = round((float) $validated['amount'], 2);
            $after = $validated['type'] === 'credit' ? $before + $amount : $before - $amount;

            if ($after < 0) {
                throw ValidationException::withMessages(['amount' => 'A debit cannot exceed the current wallet balance.']);
            }

            $lockedWallet->update(['balance' => $after]);
            WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => $validated['type'],
                'source_type' => 'admin_adjustment',
                'source_id' => $request->user()->id,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => $validated['reason'],
                'reference' => $validated['reference'] ?: 'ADMIN-'.now()->format('YmdHis').'-'.$lockedWallet->id,
                'created_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Wallet adjustment recorded with a complete ledger entry.');
    }

    public function reviewTopUp(Request $request, WalletTopUp $topUp)
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'review_notes' => ['nullable', 'required_if:decision,reject', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($topUp, $validated, $request) {
            $lockedTopUp = WalletTopUp::whereKey($topUp->id)->lockForUpdate()->firstOrFail();

            if ($lockedTopUp->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'Only pending top-ups can be reviewed.']);
            }

            if ($validated['decision'] === 'approve') {
                $lockedWallet = Wallet::whereKey($lockedTopUp->wallet_id)->lockForUpdate()->firstOrFail();
                $alreadyCredited = WalletTransaction::query()
                    ->where('source_type', 'wallet_top_up')
                    ->where('source_id', $lockedTopUp->id)
                    ->exists();

                if (! $alreadyCredited) {
                    $before = (float) $lockedWallet->balance;
                    $amount = (float) $lockedTopUp->amount;
                    $after = $before + $amount;
                    $lockedWallet->update(['balance' => $after]);
                    WalletTransaction::create([
                        'wallet_id' => $lockedWallet->id,
                        'type' => 'credit',
                        'source_type' => 'wallet_top_up',
                        'source_id' => $lockedTopUp->id,
                        'amount' => $amount,
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'description' => 'Administrator-approved wallet top-up',
                        'reference' => $lockedTopUp->external_id,
                        'created_by' => $request->user()->id,
                    ]);
                }

                $lockedTopUp->update([
                    'status' => 'paid',
                    'paid_at' => $lockedTopUp->paid_at ?: now(),
                    'reviewed_at' => now(),
                    'reviewed_by' => $request->user()->id,
                    'review_notes' => $validated['review_notes'] ?? null,
                ]);
            } else {
                $lockedTopUp->update([
                    'status' => 'failed',
                    'reviewed_at' => now(),
                    'reviewed_by' => $request->user()->id,
                    'review_notes' => $validated['review_notes'],
                ]);
            }
        });

        return back()->with('success', 'Wallet top-up review completed.');
    }

    public function transactions(Request $request, Wallet $wallet)
    {
        $wallet->load(['user']);

        $query = $wallet->transactions();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to.' 23:59:59');
        }

        $transactions = $query->latest()->paginate(20)->withQueryString();

        return view('admin.wallets.transactions', compact('wallet', 'transactions'));
    }
}
