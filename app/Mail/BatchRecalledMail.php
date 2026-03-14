<?php

namespace App\Mail;

use App\Models\Batch;
use App\Models\BatchRecall;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BatchRecalledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Batch $batch,
        public BatchRecall $recall
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AGU] ⚠️ Cảnh báo: Lô ' . $this->batch->code . ' đã bị thu hồi',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.batch-recalled');
    }
}