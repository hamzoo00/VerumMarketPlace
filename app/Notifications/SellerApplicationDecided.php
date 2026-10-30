<?php

namespace App\Notifications;

use App\Contracts\AuthenticatableAccount;
use App\Models\SellerApplication;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells an applicant what an admin decided — by email, under the bell,
 * and live on the status page they are probably watching.
 *
 * Queued, so a slow or failing SMTP host never blocks the admin's action.
 */
class SellerApplicationDecided extends InAppNotification
{
    public function __construct(
        private readonly SellerApplication $application,
        private readonly string $decision,
    ) {
        parent::__construct();
    }

    public function toMail(AuthenticatableAccount $notifiable): MailMessage
    {
        $app = config('app.name');

        return match ($this->decision) {
            SellerApplication::STATUS_APPROVED => $this->approved($notifiable, $app),
            SellerApplication::STATUS_REJECTED => $this->rejected($notifiable, $app),
            default => $this->onHold($notifiable, $app),
        };
    }

    private function approved(AuthenticatableAccount $notifiable, string $app): MailMessage
    {
        return $this->base($notifiable, $app)
            ->subject(__('Your :app seller application was approved', ['app' => $app]))
            ->line(__('Good news — your application :reference has been approved.', [
                'reference' => $this->application->reference,
            ]))
            ->line(__('Your storefront is live and you can start listing stones.'))
            ->action(__('Open your dashboard'), route('seller.dashboard'));
    }

    private function rejected(AuthenticatableAccount $notifiable, string $app): MailMessage
    {
        return $this->base($notifiable, $app)
            ->subject(__('About your :app seller application', ['app' => $app]))
            ->line(__('We could not approve application :reference this time.', [
                'reference' => $this->application->reference,
            ]))
            ->line($this->application->decision_note ?? __('No further detail was given.'))
            ->line(__('If you believe this was a mistake, reply to this email and we will take another look.'));
    }

    private function onHold(AuthenticatableAccount $notifiable, string $app): MailMessage
    {
        return $this->base($notifiable, $app)
            ->subject(__('We need a little more for your :app application', ['app' => $app]))
            ->line(__('Your application :reference is on hold while we wait for something from you.', [
                'reference' => $this->application->reference,
            ]))
            ->line($this->application->decision_note ?? __('We will be in touch with details.'))
            ->action(__('View your application'), route('seller.status'));
    }

    private function base(AuthenticatableAccount $notifiable, string $app): MailMessage
    {
        return (new MailMessage)
            ->greeting(__('Hello :name,', ['name' => $notifiable->displayName()]))
            ->salutation(__('— The :app team', ['app' => $app]));
    }

    protected function inApp(object $notifiable): array
    {
        $reference = $this->application->reference;

        return [
            'type' => 'application.'.$this->decision,
            'title' => match ($this->decision) {
                SellerApplication::STATUS_APPROVED => __('Your application was approved'),
                SellerApplication::STATUS_REJECTED => __('Your application was not approved'),
                default => __('Your application needs something from you'),
            },
            'body' => $this->decision === SellerApplication::STATUS_APPROVED
                ? __('Application :reference is approved. Your storefront is live.', ['reference' => $reference])
                : ($this->application->decision_note ?? __('Application :reference was updated.', ['reference' => $reference])),
            'url' => $this->decision === SellerApplication::STATUS_APPROVED
                ? route('seller.dashboard')
                : route('seller.status'),
            'icon' => 'file',
        ];
    }
}
