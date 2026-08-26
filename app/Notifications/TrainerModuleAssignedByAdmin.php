<?php

namespace App\Notifications;

use App\Models\TrainingModule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainerModuleAssignedByAdmin extends Notification implements ShouldQueue
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
            'title' => 'Module assigned: '.$this->module->title,
            'message' => 'MCARE administration assigned this learning module to you for '.$this->batchLabel().'.',
            'url' => route('trainer.modules.show', $this->module),
            'icon' => 'book-open',
            'module_id' => $this->module->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->module->is_published ? 'published' : 'saved as a draft';

        return (new MailMessage)
            ->subject('MCARE Module Assigned: '.$this->module->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('MCARE administration assigned a learning module to your trainer account.')
            ->line('**'.$this->module->title.'**')
            ->line('Batch: '.$this->batchLabel())
            ->line('Status: '.str($status)->headline())
            ->action('Open Module Hub', route('trainer.modules.show', $this->module))
            ->line('You can review the uploaded lesson file and manage its classwork from the MCARE Trainer portal.')
            ->salutation('MCARE Training Center');
    }

    private function batchLabel(): string
    {
        $batch = $this->module->batch;

        return $batch ? trim($batch->name.' '.$batch->year) : 'the assigned training batch';
    }
}
