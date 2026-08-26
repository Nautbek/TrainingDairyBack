<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent by ForgotPasswordController — a numeric code, not a link, because this app has no
 * web UI for the user to land on. The code is typed back into the same mobile screen that
 * asked for the email, alongside the new password (see ResetPasswordController).
 *
 * Sent synchronously (no ShouldQueue) — a password-reset code arriving late defeats the
 * point; the queue worker (see deploy/README.md) is for best-effort admin notifications,
 * not user-facing time-sensitive ones.
 */
class ResetPasswordCodeNotification extends Notification
{
    public function __construct(private readonly string $code)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Код для восстановления пароля')
            ->greeting('Восстановление пароля')
            ->line('Код для восстановления пароля: '.$this->code)
            ->line('Код действует 30 минут.')
            ->line('Если вы не запрашивали восстановление — просто проигнорируйте это письмо.');
    }
}
