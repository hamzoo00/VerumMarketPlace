<?php

namespace App\Notifications;

use App\Models\SupportCase;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells one party what an admin decided on a case.
 *
 * Sent to the buyer who raised it and to the seller it concerns, so
 * neither learns the outcome second-hand. Delivered by email, under the
 * bell and live; queued, so a slow SMTP host never blocks the decision.
 */
class CaseDecided extends InAppNotification
{
    public function __construct(
        private readonly SupportCase $case,
        /** Which side is being told — it changes the wording, not the outcome. */
        private readonly string $audience,
    ) {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = config('app.name');

        return (new MailMessage)
            ->subject(__(':kind :reference has been settled', [
                'kind' => $this->case->kindLabel(),
                'reference' => $this->case->reference,
            ]))
            ->greeting(__('Hello'))
            ->line($this->opening())
            ->line(__('Outcome: :outcome', ['outcome' => $this->case->resolutionLabel()]))
            ->when(
                $this->case->resolution_note !== null,
                fn (MailMessage $mail): MailMessage => $mail->line($this->case->resolution_note),
            )
            ->action(__('See the details'), $this->url())
            ->salutation(__('— the :app team', ['app' => $app]));
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'type' => 'case.decided',
            'title' => __(':kind :reference settled', [
                'kind' => $this->case->kindLabel(),
                'reference' => $this->case->reference,
            ]),
            'body' => $this->case->resolutionLabel(),
            'url' => $this->url(),
            'icon' => 'scale',
        ];
    }

    private function opening(): string
    {
        return $this->audience === 'seller'
            ? __('A buyer raised :kind :reference about your store, and we have now reviewed it.', [
                'kind' => mb_strtolower($this->case->kindLabel()),
                'reference' => $this->case->reference,
            ])
            : __('We have reviewed the :kind you raised, :reference.', [
                'kind' => mb_strtolower($this->case->kindLabel()),
                'reference' => $this->case->reference,
            ]);
    }

    private function url(): string
    {
        return $this->audience === 'seller'
            ? route('seller.cases')
            : route('buyer.cases');
    }
}
