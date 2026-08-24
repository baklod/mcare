<?php

namespace App\Notifications;

use App\Models\TrainingModule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LmsModulePublished extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public TrainingModule $module,
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
            'title' => 'New learning material: '.$this->module->title,
            'message' => str($this->module->description)->limit(140)->toString(),
            'url' => route('trainee.modules.show', $this->module),
            'icon' => 'book-open',
            'module_id' => $this->module->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trainerName = $this->module->trainer?->name ?? 'Your Trainer';
        $category = $this->module->categoryLabel();

        $mail = (new MailMessage)
            ->subject('New Learning Module: '.$this->module->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($trainerName.' has published new learning material for your class:')
            ->line('**'.$this->module->title.'** ('.$category.')');

        if ($this->module->topic) {
            $mail->line('Topic / Learning Outcome: '.$this->module->topic);
        }

        if ($this->module->estimated_hours) {
            $mail->line('Nominal Duration: '.$this->module->estimated_hours.' hours');
        }

        if ($this->module->due_at) {
            $mail->line('Target Completion Date: '.$this->module->due_at->format('F d, Y g:i A'));
        }

        $mail->line(str($this->module->description)->limit(200)->toString());

        return $mail
            ->action('Open Learning Material', route('trainee.modules.show', $this->module))
            ->line('Log in to MCARE to view the lesson files, handouts, and module assessments.')
            ->salutation('MCARE Training Center');
    }
}
