<?php

namespace App\Mail;

use App\Models\ChargingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SessionStarted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ChargingSession $session) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '⚡ Your Piezo Charging Session Has Started!');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.session.started');
    }
}