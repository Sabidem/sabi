<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $code,
        public int $minutes = 10,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your SabiHub password reset code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp-code');
    }
}
