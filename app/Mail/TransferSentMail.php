<?php

namespace App\Mail;

use App\Models\BatchTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BatchTransfer $transfer) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AGU] Yêu cầu nhận lô hàng từ ' . $this->transfer->fromEnterprise->name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.transfer-sent');
    }
}