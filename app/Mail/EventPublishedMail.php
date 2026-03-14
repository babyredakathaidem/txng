<?php

namespace App\Mail;

use App\Models\TraceEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventPublishedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TraceEvent $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AGU] Sự kiện đã được publish lên IPFS — Lô ' . $this->event->batch?->code,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.event-published');
    }
}