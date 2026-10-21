<?php

namespace App\Notifications;

use App\Models\SupportCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells one party what an admin decided on a case.
 *
 * Sent to the buyer who raised it and to the seller it concerns, so
 * neither learns the outcome second-hand. Delivered in-app and by email;
 * queued, so a slow SMTP host never blocks the admin's decision.
 */
class CaseDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SupportCase $case,
        /** Which side is being told — it changes the wording, not the outcome. */
        private readonly string $audience,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
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

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'case.decided',
            'reference' => $this->case->reference,
            'kind' => $this->case->kind,
            'resolution' => $this->case->resolution,
            'title' => __(':kind :reference settled', [
                'kind' => $this->case->kindLabel(),
                'reference' => $this->case->reference,
            ]),
            'body' => $this->case->resolutionLabel(),
            'url' => $this->url(),
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
