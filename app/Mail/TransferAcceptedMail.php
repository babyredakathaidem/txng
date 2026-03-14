<?php

namespace App\Mail;

use App\Models\BatchTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BatchTransfer $transfer) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AGU] ' . $this->transfer->toEnterprise->name . ' đã nhận lô hàng của bạn',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.transfer-accepted');
    }
}