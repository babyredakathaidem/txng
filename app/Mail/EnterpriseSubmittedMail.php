<?php

namespace App\Mail;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnterpriseSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Enterprise $enterprise,
        public User $submitter
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AGU] Doanh nghiệp mới cần duyệt: ' . $this->enterprise->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.enterprise-submitted',
        );
    }
}