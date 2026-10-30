<?php

namespace App\Notifications;

use App\Contracts\AuthenticatableAccount;
use App\Models\AccountNotice;
use App\Support\NoticeCatalogue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/**
 * Delivers one AccountNotice: by email, under the bell, and live.
 *
 * The words come from the stored notice, never from the catalogue again —
 * the email says exactly what the record says, even if a default has
 * since been reworded.
 */
class AccountNoticeSent extends InAppNotification
{
    public function __construct(
        private readonly AccountNotice $notice,
        private readonly string $subject,
        private readonly ?string $link = null,
        /** This notice's place in the account's warnings, if it is one. */
        private readonly ?int $warningNumber = null,
    ) {
        parent::__construct();
    }

    public function toMail(AuthenticatableAccount $notifiable): MailMessage
    {
        $app = config('app.name');

        $mail = (new MailMessage)
            ->subject($this->subject)
            ->greeting(__('Hello :name,', ['name' => $notifiable->displayName()]));

        // An admin's message may run to paragraphs; keep them apart.
        foreach (preg_split("/\R{2,}/", $this->notice->body) ?: [] as $paragraph) {
            $mail->line(trim($paragraph));
        }

        if ($this->warningNumber !== null) {
            $mail->line(trans_choice(
                '{1} This is the first warning on your account.|[2,*] This is warning number :count on your account.',
                $this->warningNumber,
            ));
        }

        $action = NoticeCatalogue::actionLabel($this->notice->kind);

        if ($action !== null && $this->link !== null) {
            $mail->action($action, $this->link);
        }

        return $mail->salutation(__('— The :app team', ['app' => $app]));
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'type' => 'notice.'.$this->notice->kind,
            'title' => $this->subject,
            'body' => Str::limit(Str::squish($this->notice->body), 180),
            'url' => $this->link,
            'icon' => NoticeCatalogue::icon($this->notice->kind),
        ];
    }
}
