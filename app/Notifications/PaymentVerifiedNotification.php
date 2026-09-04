<?php

namespace App\Notifications;

use App\Models\EnrollmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public EnrollmentApplication $application,
    ) {
        $this->onQueue('mail');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Enrollment payment verified',
            'message' => $this->application->status === EnrollmentApplication::STATUS_APPROVED
                ? 'Your payment was verified successfully and your MCARE account is approved.'
                : 'Your payment was verified successfully. Please wait while administration completes your account approval.',
            'url' => route('landing'),
            'icon' => 'badge-check',
            'enrollment_application_id' => $this->application->id,
            'payment_status' => $this->application->payment_status,
            'status' => $this->application->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verifiedByAdmin = filled($this->application->payment_verified_by_id);
        $mail = (new MailMessage)
            ->subject('MCARE payment verified successfully')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($verifiedByAdmin
                ? 'MCARE administration successfully verified your enrollment payment.'
                : 'Your enrollment payment was securely verified by MCARE.')
            ->line('Verified total: PHP '.number_format((float) $this->application->total_paid_amount, 2).'.')
            ->line('Remaining tuition balance: PHP '.number_format($this->application->remainingBalance(), 2).'.');

        if ($this->application->status === EnrollmentApplication::STATUS_APPROVED) {
            return $mail
                ->line('Your MCARE account is approved and ready. You may now sign in.')
                ->action('Log in to MCARE', route('login'))
                ->salutation('MCARE Training Center Administration');
        }

        return $mail
            ->line('Your enrollment is now waiting for final account verification by the administrator.')
            ->line('Please wait for a separate approval email before trying to log in. No further payment action is needed for account review.')
            ->action('Open MCARE status', route('landing'))
            ->salutation('MCARE Training Center Administration');
    }
}
