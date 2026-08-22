<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AdminResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable)
    {
        $url = $this->resetUrl($notifiable, $this->token);

        return (new MailMessage)
            ->subject('Reset Your LesGo Admin Password')
            ->view('emails.password-reset', [
                'user' => $notifiable,
                'url' => $url,
                'appName' => config('app.name', 'LesGo'),
            ]);
    }
}
