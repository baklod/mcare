<?php

namespace App\Notifications;

use App\Models\CareerOpportunity;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CareerOpportunityPublished extends Notification
{
    public function __construct(
        public CareerOpportunity $opportunity,
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
            'title' => 'New caregiving duty available',
            'message' => 'Estimated start '.$this->opportunity->estimated_start_date?->format('M d, Y').'. Open the Alumni Job Board for the approved care summary.',
            'url' => route('alumni.dashboard'),
            'icon' => 'briefcase',
            'opportunity_id' => $this->opportunity->id,
        ];
    }

    /**
     * This notification is currently in-app only. The mail channel can be
     * enabled later after the client's alumni contact policy is confirmed.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New MCARE caregiving duty')
            ->line('A privacy-reviewed caregiving duty is available in the MCARE Alumni Job Board.')
            ->action('Open Career Hub', route('alumni.dashboard'));
    }
}
