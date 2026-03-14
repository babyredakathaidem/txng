<?php

namespace App\Mail;

use App\Models\Enterprise;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnterpriseApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Enterprise $enterprise) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AGU] Doanh nghiệp của bạn đã được duyệt',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.enterprise-approved');
    }
}