<?php

namespace App\Policies;

use App\Models\EnrollmentApplication;
use App\Models\TrainingModule;
use App\Models\User;

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

        return $module->target_enrollment_application_id === $application->getKey()
            || (
                $module->target_enrollment_application_id === null
                && (
                    $module->training_batch_id === null
                    || (int) $module->training_batch_id === (int) $application->training_batch_id
                )
            );
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
