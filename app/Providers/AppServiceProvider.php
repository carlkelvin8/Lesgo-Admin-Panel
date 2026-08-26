<?php

namespace App\Providers;

use App\Events\OrderStatusChanged;
use App\Events\PaymentRefunded;
use App\Listeners\LogOrderStatusChange;
use App\Listeners\LogPaymentRefund;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return route('admin.password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });

        Event::listen(OrderStatusChanged::class, LogOrderStatusChange::class);
        Event::listen(PaymentRefunded::class, LogPaymentRefund::class);
    }
}
