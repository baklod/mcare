<?php

namespace App\Notifications;

use App\Models\TrainerAnnouncement;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LmsAnnouncementPublished extends Notification
{
    public function __construct(
        public TrainerAnnouncement $announcement,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->announcement->title,
            'message' => str($this->announcement->message)->limit(140)->toString(),
            'url' => route('trainee.stream'),
            'icon' => 'bell',
            'announcement_id' => $this->announcement->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New MCARE classroom announcement')
            ->line($this->announcement->title)
            ->action('Open classroom stream', route('trainee.stream'));
    }
}
