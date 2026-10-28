<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

// Arabic verification email in the shared MIDAD frame (Laravel's default is an English template).
class MidadVerifyEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $data = [
            'url' => $this->verificationUrl($notifiable),
            'name' => $notifiable->name,
            'expiresInMinutes' => (int) config('auth.verification.expire', 60),
        ];

        return (new MailMessage)
            ->subject('مِداد | تأكيد البريد الإلكتروني')
            ->view('mail.verify-email', $data)
            ->text('mail.verify-email-text', $data);
    }
}
