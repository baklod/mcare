<?php

namespace App\Policies;

use App\Models\EnrollmentApplication;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\TraineeClassworkSequence;

class TrainingModulePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') && $user->hasPermissionTo('modules.manage')
            ? true
            : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('modules.publish')
            || $user->hasPermissionTo('modules.view');
    }

    public function view(User $user, TrainingModule $module): bool
    {
        if ($this->trainerOwns($user, $module)) {
            return true;
        }

        if (! $user->hasPermissionTo('modules.view')
            || ! $module->is_published
            || ($module->available_at && $module->available_at->isFuture())) {
            return false;
        }

        $application = $this->approvedApplicationFor($user);

        if (! $application) {
            return false;
        }

        if ($application->is_historical_record
            || $application->learning_status === EnrollmentApplication::LEARNING_GRADUATED) {
            return false;
        }

        return app(TraineeClassworkSequence::class)->canAccess($application, $module);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('trainer') && $user->hasPermissionTo('modules.publish');
    }

    public function update(User $user, TrainingModule $module): bool
    {
        return $this->trainerOwns($user, $module);
    }

    public function delete(User $user, TrainingModule $module): bool
    {
        return $this->trainerOwns($user, $module);
    }

    public function publish(User $user, TrainingModule $module): bool
    {
        return $this->trainerOwns($user, $module);
    }

    private function trainerOwns(User $user, TrainingModule $module): bool
    {
        return $user->hasRole('trainer')
            && $user->hasPermissionTo('modules.publish')
            && (int) $module->trainer_id === $user->getKey();
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
