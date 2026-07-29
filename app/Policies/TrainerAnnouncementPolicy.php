<?php

namespace App\Policies;

use App\Models\EnrollmentApplication;
use App\Models\TrainerAnnouncement;
use App\Models\User;

class TrainerAnnouncementPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') && $user->hasPermissionTo('announcements.manage')
            ? true
            : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('announcements.view');
    }

    public function view(User $user, TrainerAnnouncement $announcement): bool
    {
        if ($this->trainerOwns($user, $announcement)) {
            return true;
        }

        if (! $user->hasPermissionTo('announcements.view')
            || ! $announcement->isVisibleNow()
            || ! in_array($announcement->audience, ['all', 'trainees'], true)) {
            return false;
        }

        $application = $this->approvedApplicationFor($user);

        return $application !== null
            && (
                $announcement->training_batch_id === null
                || (int) $announcement->training_batch_id === (int) $application->training_batch_id
            );
    }

    public function create(User $user): bool
    {
        return $user->hasRole('trainer')
            && $user->hasPermissionTo('announcements.manage');
    }

    public function update(User $user, TrainerAnnouncement $announcement): bool
    {
        return $this->trainerOwns($user, $announcement);
    }

    public function delete(User $user, TrainerAnnouncement $announcement): bool
    {
        return $this->trainerOwns($user, $announcement);
    }

    public function publish(User $user, TrainerAnnouncement $announcement): bool
    {
        return $this->trainerOwns($user, $announcement);
    }

    private function trainerOwns(User $user, TrainerAnnouncement $announcement): bool
    {
        return $user->hasRole('trainer')
            && $user->hasPermissionTo('announcements.manage')
            && (int) $announcement->trainer_id === $user->getKey();
    }

    private function approvedApplicationFor(User $user): ?EnrollmentApplication
    {
        return EnrollmentApplication::query()
            ->where('user_id', $user->getKey())
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->latest()
            ->first();
    }
}
