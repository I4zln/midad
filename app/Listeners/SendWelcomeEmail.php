<?php

namespace App\Listeners;

use App\Mail\WelcomeMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Welcome email for a new account. Runs after the response is sent and never throws: a mail problem
// must not block registration or show the new member an error. Logs carry no addresses or SMTP details.
class SendWelcomeEmail
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        defer(function () use ($user) {
            try {
                Mail::to($user->email)->send(new WelcomeMail($user));
            } catch (\Throwable $exception) {
                Log::warning('Midad welcome email could not be delivered.', ['user_id' => $user->id, 'error' => class_basename($exception)]);
            }
        });
    }
}
