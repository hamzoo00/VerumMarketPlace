<?php

namespace App\Notifications;

use App\Contracts\AuthenticatableAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an account holder their password changed, so an unexpected change is
 * noticed quickly.
 */
class PasswordChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return list<string> */
    public function via(AuthenticatableAccount $notifiable): array
    {
        return ['mail'];
    }

    public function __construct(private readonly string $area = 'buyer') {}

    public function toMail(AuthenticatableAccount $notifiable): MailMessage
    {
        $app = config('app.name');
        $resetUrl = route('password.request', ['area' => $this->area]);

        return (new MailMessage)
            ->subject(__('Your :app password was changed', ['app' => $app]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->displayName()]))
            ->line(__('The password on your :app account was just changed.', ['app' => $app]))
            ->line(__('If this was you, there is nothing to do.'))
            ->line(__('If it was not, reset your password immediately and contact us.'))
            ->action(__('Reset your password'), $resetUrl)
            ->salutation(__('— The :app team', ['app' => $app]));
    }
}
