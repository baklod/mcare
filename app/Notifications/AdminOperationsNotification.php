<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminOperationsNotification extends Notification
{
    use Queueable;

    /** @param array<string, mixed> $context */
    public function __construct(
        public string $title,
        public string $message,
        public string $url,
        public string $icon = 'bell',
        public string $event = 'operations.update',
        public array $context = [],
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        // Operational alerts must appear in the admin portal immediately and
        // must not wait for the separate email queue worker.
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icon,
            'event' => $this->event,
            'context' => $this->context,
        ];
    }
}
