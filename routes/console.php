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

// Sends the welcome email to addresses given on the command line (nothing is stored), e.g.
// php artisan midad:welcome-test someone@example.com other@example.com --name="قارئ"
Artisan::command('midad:welcome-test {to* : One or more recipient addresses} {--name=قارئ مِداد : Name shown in the greeting}', function (array $to) {
    $mailer = config('mail.default');
    $this->line("Mailer: {$mailer} · From: ".(config('mail.from.address') ?: '(empty)'));
    if ($mailer === 'smtp' && (! config('mail.mailers.smtp.username') || ! config('mail.mailers.smtp.password'))) {
        $this->error('MAIL_USERNAME or MAIL_PASSWORD is empty in .env.');

        return 1;
    }

    $user = new \App\Models\User(['name' => (string) $this->option('name')]);
    $failed = 0;
    foreach ($to as $address) {
        try {
            Mail::to($address)->send(new \App\Mail\WelcomeMail($user));
            $this->info($mailer === 'log' ? "{$address}: written to storage/logs/mail.log." : "{$address}: sent.");
        } catch (\Throwable $error) {
            $failed++;
            $this->error("{$address}: failed (".class_basename($error).').');
        }
    }

    return $failed ? 1 : 0;
})->purpose('Send the MIDAD welcome email to the given addresses');

// Grants the admin role to an existing account, e.g. on a host without database access:
// php artisan midad:make-admin you@example.com   (docker/start.sh runs it for MIDAD_ADMIN_EMAIL)
Artisan::command('midad:make-admin {email}', function (string $email) {
    $user = \App\Models\User::query()->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])->first();
    if (! $user) {
        $this->warn("No account with {$email} yet. Sign up with it first, then restart.");

        return 0;
    }

    $user->forceFill(['is_admin' => true])->save();
    $this->info("{$user->email} is now an admin.");

    return 0;
})->purpose('Give an existing MIDAD account the admin role');
