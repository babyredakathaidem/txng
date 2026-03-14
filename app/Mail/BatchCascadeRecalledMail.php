<?php

namespace App\Mail;

use App\Models\Batch;
use App\Models\BatchRecall;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Email cảnh báo cascade recall — gửi đến Admin của DN đang sở hữu
 * các lô hậu duệ bị thu hồi kéo theo.
 *
 * Khác với BatchRecalledMail (gửi cho DN phát lệnh), mail này gửi cho
 * các DN "bị ảnh hưởng" — tức đang cầm lô hàng xuất phát từ lô gốc.
 */
class BatchCascadeRecalledMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Batch       $rootBatch       Lô gốc bị thu hồi
     * @param BatchRecall $rootRecall      Lệnh thu hồi gốc
     * @param Collection  $affectedBatches Các lô hậu duệ thuộc DN này bị kéo theo
     */
    public function __construct(
        public Batch $rootBatch,
        public BatchRecall $rootRecall,
        public Collection $affectedBatches,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AGU] ⚠️ KHẨN: ' . $this->affectedBatches->count()
                . ' lô hàng bị thu hồi dây chuyền từ lô ' . $this->rootBatch->code,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.batch-cascade-recalled');
    }
}