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
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Enrollment registration saved',
            'message' => 'Your '.($this->application->program ?: 'training program').' enrollment was saved. Continue to payment; Admin Review begins after the required payment is verified.',
            'url' => route('payments.show'),
            'icon' => 'clipboard-check',
            'enrollment_application_id' => $this->application->id,
            'enrollment_number' => $this->application->enrollment_number,
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
            ->line('Your enrollment number is '.($this->application->enrollment_number ?: 'being issued').'. Keep this number if you want to pay later.')
            ->line('Next step: complete the required enrollment payment. Your application will be released to MCARE administration for document and account review only after payment is verified.');

        if ($this->application->batch) {
            $mail->line('Training batch: '.$this->application->batch->name.' '.$this->application->batch->year.'.');
        }

        return $mail
            ->action('Continue payment', route('payments.show', array_filter([
                'enrollment_number' => $this->application->enrollment_number,
            ])))
            ->line('Keep this email for your records. MCARE will notify you after payment verification and again when the application status changes.')
            ->salutation('MCARE Training Center Administration');
    }
}
