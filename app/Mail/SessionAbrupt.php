<?php

namespace App\Mail;

use App\Models\ChargingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SessionAbrupt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ChargingSession $session) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '⚠️ Your Session Was Stopped by the Admin');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.session.abrupt');
    }
}