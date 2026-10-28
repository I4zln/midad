<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Checks the real mail setup by sending one short Arabic message.
Artisan::command('midad:mail-test {to}', function (string $to) {
    $mailer = config('mail.default');
    $this->line("Mailer: {$mailer} · Host: ".config("mail.mailers.{$mailer}.host", '-').' · From: '.(config('mail.from.address') ?: '(empty)'));

    if ($mailer === 'log') {
        $this->warn('MAIL_MAILER is "log": the message goes to storage/logs/mail.log, not to an inbox.');
    }
    if ($mailer === 'smtp' && (! config('mail.mailers.smtp.username') || ! config('mail.mailers.smtp.password'))) {
        $this->error('MAIL_USERNAME or MAIL_PASSWORD is empty in .env.');

        return 1;
    }

    try {
        Mail::raw("هذه رسالة تجريبية من مِداد للتأكد من إعداد البريد.\n\nفريق مِداد | لأن للكلمات أثرًا يبقى", function ($message) use ($to) {
            $message->to($to)->subject('مِداد | رسالة تجريبية');
        });
    } catch (\Throwable $error) {
        $this->error('Sending failed: '.$error->getMessage());
        $this->line('Gmail tips: use an App Password (not your normal password), 2-Step Verification must be on, and MAIL_FROM_ADDRESS must be the same Gmail address.');

        return 1;
    }

    $this->info($mailer === 'log' ? 'Written to storage/logs/mail.log.' : "Sent. Check the inbox (and spam) of {$to}.");

    return 0;
})->purpose('Send a test email to check the MIDAD mail settings');
