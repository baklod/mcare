<?php

namespace App\Notifications;

use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LmsQuizPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public Quiz $quiz,
    ) {
        $this->onQueue('mail');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User || ! $this->isStillEligible($notifiable)) {
            return [];
        }

        return filled($notifiable->email)
            ? ['database', 'mail']
            : ['database'];
    }

    private function isStillEligible(User $notifiable): bool
    {
        if ($notifiable->role !== 'trainee') {
            return false;
        }

        $quiz = Quiz::query()->find($this->quiz->getKey());

        if (! $quiz || ! $quiz->isReleasedAt()) {
            return false;
        }

        return EnrollmentApplication::query()
            ->where('user_id', $notifiable->id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->where('learning_status', '!=', EnrollmentApplication::LEARNING_GRADUATED)
            ->where('is_historical_record', false)
            ->get()
            ->contains(fn (EnrollmentApplication $application): bool => $quiz->targets($application));
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New assessment: '.$this->quiz->title,
            'message' => 'A new quiz has been published for your classwork.',
            'url' => route('trainee.quizzes.show', $this->quiz),
            'icon' => 'list-check',
            'quiz_id' => $this->quiz->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trainerName = $this->quiz->trainer?->name ?? 'Your Trainer';
        $moduleTitle = $this->quiz->trainingModule?->title;

        $mail = (new MailMessage)
            ->subject('New Quiz Available: '.$this->quiz->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($trainerName.' has published a new assessment:')
            ->line('**'.$this->quiz->title.'**'.($moduleTitle ? ' (Module: '.$moduleTitle.')' : ''));

        if ($this->quiz->time_limit_minutes) {
            $mail->line('Time Limit: '.$this->quiz->time_limit_minutes.' minutes');
        }

        if ($this->quiz->passing_score_percent) {
            $mail->line('Passing Score: '.number_format((float) $this->quiz->passing_score_percent, 0).'%');
        }

        if ($this->quiz->attempt_limit) {
            $mail->line('Allowed Attempts: '.$this->quiz->attempt_limit);
        }

        if ($this->quiz->due_at) {
            $mail->line('Due Date: '.$this->quiz->due_at->format('F d, Y g:i A'));
        }

        if ($this->quiz->instructions) {
            $mail->line('Instructions: '.str($this->quiz->instructions)->limit(200)->toString());
        }

        return $mail
            ->action('Open Quiz', route('trainee.quizzes.show', $this->quiz))
            ->line('Be sure to complete your assessment before the specified deadline.')
            ->salutation('MCARE Training Center');
    }
}
