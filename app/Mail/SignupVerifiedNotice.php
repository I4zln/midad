<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignupVerifiedNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $displayName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'عضو جديد في مِداد');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.signup-verified');
    }
}
