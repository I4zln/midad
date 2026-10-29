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

// Copies the library from this machine's database to another one, e.g. the host's PostgreSQL:
// put the host's External Database URL in .env as MIDAD_COPY_TO_URL, then php artisan midad:copy-data --dry-run, then without it.
// The target's own rows in these tables are replaced. Uploaded pictures stay on this machine, so their paths are left empty there.
Artisan::command('midad:copy-data {--dry-run : Only count what would be copied} {--force : Do not ask before replacing}', function () {
    $tables = ['users', 'quotes', 'quote_likes', 'quote_favorites', 'quote_comments', 'user_follows', 'user_notifications', 'special_profile_messages'];
    $source = \Illuminate\Support\Facades\DB::connection();
    foreach ($tables as $table) {
        $this->line(str_pad($table, 26).$source->table($table)->count().' rows');
    }
    if ($this->option('dry-run')) {
        return 0;
    }

    $url = (string) env('MIDAD_COPY_TO_URL', '');
    if ($url === '') {
        $this->error('MIDAD_COPY_TO_URL is empty in .env.');

        return 1;
    }
    config(['database.connections.copy_target' => ['driver' => 'pgsql', 'url' => $url, 'charset' => 'utf8', 'prefix' => '', 'search_path' => 'public', 'sslmode' => 'require']]);
    $target = \Illuminate\Support\Facades\DB::connection('copy_target');
    $this->line('Target: '.$target->getDriverName().' · '.(parse_url($url, PHP_URL_HOST) ?: '?'));
    if (! $this->option('force') && ! $this->confirm('Replace these tables on the target (and sign everyone out there)?')) {
        return 1;
    }

    $target->transaction(function () use ($tables, $source, $target) {
        $target->statement('truncate table '.implode(', ', array_reverse($tables)).', sessions restart identity cascade');
        foreach ($tables as $table) {
            $source->table($table)->orderBy('id')->chunk(200, function ($rows) use ($table, $target) {
                $target->table($table)->insert($rows->map(function ($row) use ($table) {
                    $row = (array) $row;
                    if ($table === 'users') {
                        $row['avatar_path'] = $row['cover_path'] = null;
                        $row['is_admin'] = (bool) $row['is_admin']; // SQLite keeps 0/1; PostgreSQL wants a boolean
                    }

                    return $row;
                })->all());
            });
            $target->statement("select setval(pg_get_serial_sequence('{$table}', 'id'), coalesce(max(id), 1), max(id) is not null) from {$table}");
            $this->info(str_pad($table, 26).$target->table($table)->count().' rows on the target');
        }
    });
    $target->table('cache')->delete();

    return 0;
})->purpose('Copy users, quotes and activity from this database to MIDAD_COPY_TO_URL');
