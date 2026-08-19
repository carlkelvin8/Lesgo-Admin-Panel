<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $query = Wallet::with(['user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%")
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
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $transactions = $query->latest()->paginate(20)->withQueryString();

        return view('admin.wallets.transactions', compact('wallet', 'transactions'));
    }
}
