<?php

namespace App\Notifications;

use App\Contracts\AuthenticatableAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Password reset link.
 *
 * Carries the account area so a buyer's link opens the buyer form and a
 * seller's opens the seller form.
 */
class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly string $area,
    ) {}

    /** @return list<string> */
    public function via(AuthenticatableAccount $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(AuthenticatableAccount $notifiable): MailMessage
    {
        $app = config('app.name');
        $minutes = config('auth.passwords.'.($this->area === 'seller' ? 'seller_accounts' : 'buyers').'.expire', 60);

        $url = route('account.password.reset', [
            'area' => config('verum_routes.'.$this->area),
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject(__('Reset your :app password', ['app' => $app]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->displayName()]))
            ->line(__('You asked to reset the password on your :app account.', ['app' => $app]))
            ->action(__('Choose a new password'), $url)
            ->line(__('This link expires in :count minutes.', ['count' => $minutes]))
            ->line(__('If you did not ask for this, you can ignore this email; nothing has changed.'))
            ->salutation(__('— The :app team', ['app' => $app]));
    }
}
