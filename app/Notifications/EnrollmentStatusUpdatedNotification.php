<?php

namespace App\Notifications;

use App\Models\EnrollmentApplication;
use App\Support\AccountPortal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentStatusUpdatedNotification extends Notification implements ShouldQueue
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
        return filled($notifiable->email)
            ? ['database', 'mail']
            : ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $approved = $this->application->status === EnrollmentApplication::STATUS_APPROVED;
        $denied = $this->application->status === EnrollmentApplication::STATUS_DENIED;

        return [
            'title' => $approved
                ? 'MCARE account approved'
                : ($denied ? 'Enrollment application not approved' : 'Enrollment status updated'),
            'message' => $approved
                ? 'Your account was verified and approved. You can now log in to the MCARE trainee portal.'
                : ($denied
                    ? 'Your Caregiving NC II enrollment application was not approved. Review the administrator note for the reason and next steps.'
                    : 'Your Caregiving NC II application is now '.$this->application->statusLabel().'.'),
            'url' => AccountPortal::urlFor($notifiable),
            'icon' => $this->application->status === EnrollmentApplication::STATUS_APPROVED ? 'badge-check' : 'clipboard-check',
            'enrollment_application_id' => $this->application->id,
            'status' => $this->application->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->application->status === EnrollmentApplication::STATUS_APPROVED) {
            return (new MailMessage)
                ->subject('Your MCARE account is approved - you can now log in')
                ->greeting('Hello '.$notifiable->name.',')
                ->line('MCARE administration verified and approved your Caregiving NC II enrollment account.')
                ->line('Your account is now active. You may log in and open the trainee portal.')
                ->action('Log in to MCARE', route('login'))
                ->line('Use the same verified Gmail account or MCARE credentials from your enrollment.')
                ->salutation('MCARE Training Center Administration');
        }

        if ($this->application->status === EnrollmentApplication::STATUS_DENIED) {
            $mail = (new MailMessage)
                ->subject('Important: Your MCARE enrollment application was not approved')
                ->greeting('Hello '.$notifiable->name.',')
                ->line('MCARE administration completed the review of your Caregiving NC II enrollment application and did not approve the account.');

            if (filled($this->application->admin_notes)) {
                $mail->line('Administrator note: '.$this->application->admin_notes);
            }

            return $mail
                ->line('If your enrollment payment was already verified, it remains recorded. Please contact MCARE administration regarding correction, resubmission, or other next steps.')
                ->action('Open MCARE', route('landing'))
                ->salutation('MCARE Training Center Administration');
        }

        $mail = (new MailMessage)
            ->subject('MCARE enrollment status: '.$this->application->statusLabel())
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your Caregiving NC II enrollment application status is now '.$this->application->statusLabel().'.');

        if (filled($this->application->admin_notes)) {
            $mail->line('Administrator note: '.$this->application->admin_notes);
        }

        return $mail
            ->action('Open MCARE', route('login'))
            ->line('Sign in to MCARE to review your enrollment and next steps.')
            ->salutation('MCARE Training Center Administration');
    }
}
