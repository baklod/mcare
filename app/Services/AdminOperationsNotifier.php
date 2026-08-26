<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AdminOperationsNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AdminOperationsNotifier
{
    /** @param array<string, mixed> $context */
    public function notify(
        string $title,
        string $message,
        string $url,
        string $icon = 'bell',
        string $event = 'operations.update',
        array $context = [],
    ): void {
        try {
            $admins = User::query()
                ->where('role', 'admin')
                ->get();

            if ($admins->isEmpty()) {
                return;
            }

            Notification::send($admins, new AdminOperationsNotification(
                title: $title,
                message: $message,
                url: $url,
                icon: $icon,
                event: $event,
                context: $context,
            ));
        } catch (Throwable $exception) {
            // A notification-center failure must not undo an enrollment or
            // payment record that was already accepted by the application.
            report($exception);
        }
    }
}
