<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletAdjusted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Wallet $wallet,
        public readonly WalletTransaction $transaction,
    ) {}
}
