<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Sent once after registration (App\Listeners\SendWelcomeEmail). Holds only the display name — never credentials.
class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), 'مِداد | MIDAD'),
            subject: 'أهلًا بك في مِداد',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.welcome',
            text: 'mail.welcome-text',
            with: [
                'name' => $this->user->name,
                'browseUrl' => route('quotes.index'),
                'shareUrl' => route('quotes.create'),
            ],
        );
    }
}
