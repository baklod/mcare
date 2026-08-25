<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TrainingModule;
use App\Models\User;
use App\Notifications\LmsModulePublished;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class RollingModuleReleaseService
{
    /**
     * Make a delivery current and snapshot its audience. A batch activation
     * closes the previous batch delivery for future enrollees, but its
     * existing assignments remain available to the trainees who received it.
     */
    public function activate(TrainingModule $module): Collection
    {
        $unlockedUserIds = DB::transaction(function () use ($module): Collection {
            $lockedModule = TrainingModule::query()->lockForUpdate()->findOrFail($module->id);

            if ($lockedModule->delivery_status === TrainingModule::DELIVERY_CLOSED) {
                throw ValidationException::withMessages([
                    'module' => 'A closed historical delivery cannot be reopened. Create a new release or a private catch-up assignment instead.',
                ]);
            }

            if (! $lockedModule->is_published) {
                throw ValidationException::withMessages([
                    'is_published' => 'Publish the module before activating its trainee delivery.',
                ]);
            }

            if ($lockedModule->target_enrollment_application_id === null) {
                if (! $lockedModule->training_batch_id) {
                    throw ValidationException::withMessages([
                        'training_batch_id' => 'An active rolling module must belong to a training batch.',
                    ]);
                }

                // Serialize activation for this batch so two simultaneous
                // publishes cannot leave two current modules active.
                DB::table('training_batches')
                    ->where('id', $lockedModule->training_batch_id)
                    ->lockForUpdate()
                    ->first();

                TrainingModule::query()
                    ->where('training_batch_id', $lockedModule->training_batch_id)
                    ->whereNull('target_enrollment_application_id')
                    ->whereKeyNot($lockedModule->id)
                    ->where('delivery_status', TrainingModule::DELIVERY_ACTIVE)
                    ->update([
                        'delivery_status' => TrainingModule::DELIVERY_CLOSED,
                        'closed_at' => now(),
                    ]);
            }

            $lockedModule->forceFill([
                'delivery_status' => TrainingModule::DELIVERY_ACTIVE,
                'activated_at' => $lockedModule->activated_at ?: now(),
                'closed_at' => null,
            ])->save();

            $applications = $lockedModule->target_enrollment_application_id !== null
                ? EnrollmentApplication::query()
                    ->whereKey($lockedModule->target_enrollment_application_id)
                    ->where('status', EnrollmentApplication::STATUS_APPROVED)
                    ->get()
                : EnrollmentApplication::query()
                    ->where('training_batch_id', $lockedModule->training_batch_id)
                    ->where('status', EnrollmentApplication::STATUS_APPROVED)
                    ->where(function ($query): void {
                        $query->whereNull('learning_status')
                            ->orWhere('learning_status', EnrollmentApplication::LEARNING_ACTIVE);
                    })
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

            return $applications
                ->map(fn (EnrollmentApplication $application) => $this->assignLocked($application, $lockedModule))
                ->filter(fn (ModuleProgress $progress): bool => $progress->isAccessible())
                ->map(fn (ModuleProgress $progress): ?int => $progress->application?->user_id)
                ->filter()
                ->unique()
                ->values();
        }, 3);

        $this->notifyUnlocked($module->fresh(['trainer']), $unlockedUserIds);

        return $unlockedUserIds;
    }

    /** Close a delivery only for future enrollment snapshots. */
    public function close(TrainingModule $module): void
    {
        DB::transaction(function () use ($module): void {
            $lockedModule = TrainingModule::query()->lockForUpdate()->findOrFail($module->id);

            if ($lockedModule->delivery_status !== TrainingModule::DELIVERY_ACTIVE) {
                return;
            }

            $lockedModule->forceFill([
                'delivery_status' => TrainingModule::DELIVERY_CLOSED,
                'closed_at' => now(),
            ])->save();
        }, 3);
    }

    /**
     * Snapshot only the module active at approval time. Older closed modules
     * are deliberately not backfilled into the new trainee's assignments.
     */
    public function assignCurrentTo(EnrollmentApplication $application): Collection
    {
        $assigned = DB::transaction(function () use ($application): Collection {
            $lockedApplication = EnrollmentApplication::query()
                ->lockForUpdate()
                ->findOrFail($application->id);

            if ($lockedApplication->status !== EnrollmentApplication::STATUS_APPROVED
                || ($lockedApplication->learning_status !== null
                    && $lockedApplication->learning_status !== EnrollmentApplication::LEARNING_ACTIVE)) {
                return collect();
            }

            if (! $lockedApplication->learning_started_at) {
                $lockedApplication->forceFill(['learning_started_at' => now()])->save();
            }

            $modules = TrainingModule::query()
                ->where('is_published', true)
                ->where('delivery_status', TrainingModule::DELIVERY_ACTIVE)
                ->where(function ($query) use ($lockedApplication): void {
                    $query->where(function ($batch) use ($lockedApplication): void {
                        $batch->whereNull('target_enrollment_application_id')
                            ->where('training_batch_id', $lockedApplication->training_batch_id);
                    })->orWhere('target_enrollment_application_id', $lockedApplication->id);
                })
                ->orderBy('target_enrollment_application_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            return $modules->map(function (TrainingModule $module) use ($lockedApplication): ModuleProgress {
                return $this->assignLocked($lockedApplication, $module);
            });
        }, 3);

        $assigned
            ->filter(fn (ModuleProgress $progress): bool => $progress->isAccessible())
            ->each(function (ModuleProgress $progress): void {
                $this->notifyUnlocked(
                    $progress->module()->with('trainer')->firstOrFail(),
                    collect([$progress->application?->user_id])->filter(),
                );
            });

        return $assigned;
    }

    /** Unlock exactly one next assignment after trainer validation. */
    public function unlockNext(EnrollmentApplication $application): ?ModuleProgress
    {
        $next = DB::transaction(function () use ($application): ?ModuleProgress {
            $lockedApplication = EnrollmentApplication::query()
                ->lockForUpdate()
                ->findOrFail($application->id);

            $blocking = ModuleProgress::query()
                ->where('enrollment_application_id', $lockedApplication->id)
                ->where('status', '!=', ModuleProgress::STATUS_LOCKED)
                ->where(function ($query): void {
                    $query->where('status', '!=', ModuleProgress::STATUS_COMPLETED)
                        ->orWhere('competency_outcome', '!=', ModuleProgress::OUTCOME_COMPETENT)
                        ->orWhereNull('competency_outcome');
                })
                ->exists();

            if ($blocking) {
                return null;
            }

            $next = ModuleProgress::query()
                ->where('enrollment_application_id', $lockedApplication->id)
                ->where('status', ModuleProgress::STATUS_LOCKED)
                ->orderBy('sequence_number')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $next) {
                return null;
            }

            $next->forceFill([
                'status' => ModuleProgress::STATUS_NOT_STARTED,
                'unlocked_at' => now(),
            ])->save();

            return $next->fresh(['application', 'module.trainer']);
        }, 3);

        if ($next) {
            $this->notifyUnlocked(
                $next->module,
                collect([$next->application?->user_id])->filter(),
            );
        }

        return $next;
    }

    private function assignLocked(
        EnrollmentApplication $application,
        TrainingModule $module,
    ): ModuleProgress {
        $existing = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $module->id)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            $existing->loadMissing('application');

            if ($existing->status === ModuleProgress::STATUS_LOCKED
                && ! $this->hasBlockingAssignment($application, $existing->id)) {
                $existing->forceFill([
                    'status' => ModuleProgress::STATUS_NOT_STARTED,
                    'unlocked_at' => now(),
                ])->save();
            }

            return $existing;
        }

        $sequence = ((int) ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->lockForUpdate()
            ->max('sequence_number')) + 1;
        $isLocked = $this->hasBlockingAssignment($application);

        $progress = ModuleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'sequence_number' => $sequence,
            'status' => $isLocked ? ModuleProgress::STATUS_LOCKED : ModuleProgress::STATUS_NOT_STARTED,
            'progress_percent' => 0,
            'assigned_at' => now(),
            'unlocked_at' => $isLocked ? null : now(),
        ]);
        $progress->setRelation('application', $application);

        return $progress;
    }

    private function hasBlockingAssignment(
        EnrollmentApplication $application,
        ?int $exceptProgressId = null,
    ): bool {
        return ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->when($exceptProgressId, fn ($query) => $query->whereKeyNot($exceptProgressId))
            ->where(function ($query): void {
                $query->where('status', '!=', ModuleProgress::STATUS_COMPLETED)
                    ->orWhere('competency_outcome', '!=', ModuleProgress::OUTCOME_COMPETENT)
                    ->orWhereNull('competency_outcome');
            })
            ->exists();
    }

    private function notifyUnlocked(TrainingModule $module, Collection $userIds): void
    {
        if ($userIds->isEmpty() || $module->available_at?->isFuture()) {
            return;
        }

        $trainees = User::query()
            ->whereIn('id', $userIds)
            ->where('role', 'trainee')
            ->get();

        if ($trainees->isNotEmpty()) {
            Notification::send($trainees, new LmsModulePublished($module));
        }
    }
}
