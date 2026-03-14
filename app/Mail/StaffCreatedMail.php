<?php

namespace App\Mail;

use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $staff,
        public Enterprise $enterprise,
        public string $plainPassword
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AGU] Tài khoản nhân viên của bạn đã được tạo',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.staff-created');
    }
}