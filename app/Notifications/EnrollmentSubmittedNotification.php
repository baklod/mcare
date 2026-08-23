<?php

namespace App\Notifications;

use App\Models\EnrollmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentSubmittedNotification extends Notification implements ShouldQueue
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
        return [
            'title' => 'Enrollment application received',
            'message' => 'Your Caregiving NC II enrollment was saved. Continue to payment and watch for review updates.',
            'url' => route('payment.show'),
            'icon' => 'clipboard-check',
            'enrollment_application_id' => $this->application->id,
            'status' => $this->application->status,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->application->loadMissing('batch');

        $mail = (new MailMessage)
            ->subject('MCARE enrollment application received')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We received your Caregiving NC II enrollment application.')
            ->line('Current status: '.$this->application->statusLabel().'.');

        if ($this->application->batch) {
            $mail->line('Training batch: '.$this->application->batch->name.' '.$this->application->batch->year.'.');
        }

        return $mail
            ->action('Open MCARE', route('login'))
            ->line('Keep this email for your records. MCARE will notify you when the application status changes.')
            ->salutation('MCARE Training Center Administration');
    }
}
