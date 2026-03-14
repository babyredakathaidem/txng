<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('[AGU] Xác minh địa chỉ email của bạn')
            ->greeting('Xin chào!')
            ->line('Cảm ơn bạn đã đăng ký tài khoản AGU Traceability.')
            ->line('Bấm vào nút bên dưới để xác minh địa chỉ email.')
            ->action('Xác minh email', $url)
            ->line('Liên kết này sẽ hết hạn sau 60 phút.')
            ->line('Nếu bạn không thực hiện đăng ký, hãy bỏ qua email này.')
            ->salutation('Trân trọng, AGU Traceability');
    }
}