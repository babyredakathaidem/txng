<?php

namespace App\Mail;

use App\Models\Enterprise;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnterpriseBlockedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Enterprise $enterprise) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AGU] Tài khoản doanh nghiệp của bạn đã bị khóa',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.enterprise-blocked');
    }
}