<?php

namespace App\Services;

use App\Models\AdminAnnouncement;
use App\Models\EnrollmentApplication;
use App\Models\TrainerAnnouncement;
use App\Models\User;
use App\Notifications\AdminAnnouncementNotification;
use App\Notifications\LmsAnnouncementPublished;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Throwable;

class AnnouncementDeliveryService
{
    private const TYPE_ADMIN = 'admin';

    private const TYPE_TRAINER = 'trainer';

    public function deliverTrainerAnnouncement(
        TrainerAnnouncement $announcement,
        iterable $recipients,
        string $reason = 'publication',
    ): int {
        return $this->deliverToRecipients(
            $recipients,
            self::TYPE_TRAINER,
            (int) $announcement->getKey(),
            fn () => new LmsAnnouncementPublished($announcement),
            $reason,
        );
    }

    public function deliverAdminAnnouncement(
        AdminAnnouncement $announcement,
        iterable $recipients,
        string $reason = 'publication',
    ): int {
        return $this->deliverToRecipients(
            $recipients,
            self::TYPE_ADMIN,
            (int) $announcement->getKey(),
            fn () => new AdminAnnouncementNotification($announcement),
            $reason,
        );
    }

    public function catchUpFor(User $user): int
    {
        if (! $user->hasRole('trainee')) {
            return 0;
        }

        $application = EnrollmentApplication::query()
            ->where('user_id', $user->id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->latest()
            ->first();

        if (! $application) {
            return 0;
        }

        $delivered = 0;

        $trainerAnnouncements = TrainerAnnouncement::query()
            ->where('is_published', true)
            ->whereIn('audience', ['all', 'trainees'])
            ->where(fn ($query) => $query
                ->whereNull('posted_at')
                ->orWhere('posted_at', '<=', now()))
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->where(function ($query) use ($application) {
                $query->whereNull('training_batch_id')
                    ->orWhere('training_batch_id', $application->training_batch_id);
            })
            ->whereNotExists(function ($query) use ($user) {
                $query->selectRaw('1')
                    ->from('announcement_deliveries')
                    ->whereColumn('announcement_deliveries.announcement_id', 'trainer_announcements.id')
                    ->where('announcement_deliveries.announcement_type', self::TYPE_TRAINER)
                    ->where('announcement_deliveries.user_id', $user->id);
            })
            ->get();

        foreach ($trainerAnnouncements as $announcement) {
            $delivered += $this->safelyDeliverForCatchUp(
                fn () => $this->deliverTrainerAnnouncement($announcement, [$user], 'login_catch_up'),
            );
        }

        $adminAnnouncements = AdminAnnouncement::query()
            ->visibleTo($user, $application->training_batch_id)
            ->whereNotExists(function ($query) use ($user) {
                $query->selectRaw('1')
                    ->from('announcement_deliveries')
                    ->whereColumn('announcement_deliveries.announcement_id', 'admin_announcements.id')
                    ->where('announcement_deliveries.announcement_type', self::TYPE_ADMIN)
                    ->where('announcement_deliveries.user_id', $user->id);
            })
            ->get();

        foreach ($adminAnnouncements as $announcement) {
            $delivered += $this->safelyDeliverForCatchUp(
                fn () => $this->deliverAdminAnnouncement($announcement, [$user], 'login_catch_up'),
            );
        }

        return $delivered;
    }

    /**
     * @param  iterable<User>  $recipients
     * @param  callable(): Notification  $notificationFactory
     */
    private function deliverToRecipients(
        iterable $recipients,
        string $announcementType,
        int $announcementId,
        callable $notificationFactory,
        string $reason,
    ): int {
        $delivered = 0;

        foreach ($recipients as $recipient) {
            $claimed = DB::table('announcement_deliveries')->insertOrIgnore([
                'user_id' => $recipient->id,
                'announcement_type' => $announcementType,
                'announcement_id' => $announcementId,
                'delivery_reason' => $reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($claimed !== 1) {
                continue;
            }

            try {
                $recipient->notify($notificationFactory());
                $delivered++;
            } catch (Throwable $exception) {
                DB::table('announcement_deliveries')->where([
                    'user_id' => $recipient->id,
                    'announcement_type' => $announcementType,
                    'announcement_id' => $announcementId,
                ])->delete();

                throw $exception;
            }
        }

        return $delivered;
    }

    /** @param callable(): int $delivery */
    private function safelyDeliverForCatchUp(callable $delivery): int
    {
        try {
            return $delivery();
        } catch (Throwable $exception) {
            report($exception);

            return 0;
        }
    }
}
