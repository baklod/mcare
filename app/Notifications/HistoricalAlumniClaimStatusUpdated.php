<?php

namespace App\Notifications;

use App\Models\HistoricalAlumniClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HistoricalAlumniClaimStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public HistoricalAlumniClaim $claim)
    {
        $this->onQueue('mail');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $approved = $this->claim->status === HistoricalAlumniClaim::STATUS_APPROVED;

        return [
            'title' => $approved ? 'Historical alumni account verified' : 'Historical alumni claim updated',
            'message' => $approved
                ? 'MCARE verified your historical training record. Your Alumni Career Hub access is ready.'
                : 'MCARE could not approve your historical alumni claim yet. Review the administrator note and contact the training center.',
            'url' => $approved ? route('login') : route('alumni.claim.create'),
            'icon' => $approved ? 'badge-check' : 'clipboard-check',
            'historical_alumni_claim_id' => $this->claim->id,
            'status' => $this->claim->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->claim->status === HistoricalAlumniClaim::STATUS_APPROVED;
        $mail = (new MailMessage)
            ->subject($approved ? 'Your MCARE alumni account is verified' : 'Update on your MCARE alumni claim')
            ->greeting('Hello '.$notifiable->name.',');

        if ($approved) {
            return $mail
                ->line('MCARE administration verified your identity and historical training record.')
                ->line('Your account is now recorded as a verified graduate. You can sign in to open the Alumni Career Hub.')
                ->action('Sign in to MCARE', route('login'))
                ->salutation('MCARE Training Center Administration');
        }

        $mail->line('MCARE could not approve your historical alumni claim yet.');
        if (filled($this->claim->admin_notes)) {
            $mail->line('Administrator note: '.$this->claim->admin_notes);
        }

        return $mail
            ->action('Review alumni claim instructions', route('alumni.claim.create'))
            ->line('Bring your valid ID and original COTC or TOR when you visit MCARE.')
            ->salutation('MCARE Training Center Administration');
    }
}
