<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * German, queued password reset mail.
 */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Passwort zurücksetzen – '.config('app.name'))
            ->greeting('Hallo '.$notifiable->name.',')
            ->line('du erhältst diese E-Mail, weil für dein Konto ein neues Passwort angefordert wurde.')
            ->action('Passwort zurücksetzen', $url)
            ->line("Der Link ist {$minutes} Minuten gültig.")
            ->line('Falls du kein neues Passwort angefordert hast, musst du nichts tun.')
            ->salutation('Viele Grüße, dein '.config('app.name').'-Team');
    }
}
