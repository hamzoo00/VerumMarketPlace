<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Something happened to money: paid, refunded, disputed, premium renewed.
 *
 * One class for all of them because they read the same way — a title, a
 * sentence and where to look — and the sentence is written by the action
 * that knows what happened (Actions\Payments). Email, the bell and live,
 * like every InAppNotification.
 */
class PaymentUpdate extends InAppNotification
{
    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $body,
        private readonly ?string $url = null,
        private readonly string $icon = 'card',
    ) {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting(__('Hello'))
            ->line($this->body)
            ->when($this->url !== null, fn (MailMessage $mail): MailMessage => $mail->action(__('See the details'), (string) $this->url))
            ->salutation(__('— the :app team', ['app' => config('app.name')]));
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'type' => 'payment.'.$this->type,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
        ];
    }
}
