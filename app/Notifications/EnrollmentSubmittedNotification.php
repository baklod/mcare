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
            'title' => 'Enrollment registration saved',
            'message' => 'Your '.($this->application->program ?: 'training program').' enrollment was saved. Continue to payment; Admin Review begins after the required payment is verified.',
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
            ->subject('MCARE enrollment registration saved')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We saved your '.($this->application->program ?: 'training program').' enrollment registration.')
            ->line('Next step: complete the required enrollment payment. Your application will be released to MCARE administration for document and account review only after payment is verified.');

        if ($this->application->batch) {
            $mail->line('Training batch: '.$this->application->batch->name.' '.$this->application->batch->year.'.');
        }

        return $mail
            ->action('Open MCARE', route('login'))
            ->line('Keep this email for your records. MCARE will notify you after payment verification and again when the application status changes.')
            ->salutation('MCARE Training Center Administration');
    }
}
