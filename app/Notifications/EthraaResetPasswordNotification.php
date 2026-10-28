<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class EthraaResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('مِداد | طلب إعادة تعيين كلمة المرور')
            ->view('mail.password-reset', [
                'url' => $url,
                'expiresInMinutes' => (int) config('auth.passwords.users.expire', 60),
            ])
            ->text('mail.password-reset-text', [
                'url' => $url,
                'expiresInMinutes' => (int) config('auth.passwords.users.expire', 60),
            ]);
    }
}
