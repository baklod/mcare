<?php

namespace App\Services;

use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingModule;
use App\Models\TrainingSubmodule;
use App\Models\TrainingSubmoduleProgress;
use App\Models\User;
use App\Support\CaregivingNcIiCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModuleSubmoduleService
{
    /**
     * Ensure the release has a stable child snapshot. Catalog outcomes are the
     * authoritative source; custom assessed modules create an optional unit.
     *
     * @param  list<string>  $customOutcomeTitles
     * @return Collection<int, TrainingSubmodule>
     */
    public function ensureStructure(TrainingModule $module, array $customOutcomeTitles = []): Collection
    {
        $module->refresh();
        $unit = $module->competencyUnit;

        if (! $unit && filled($module->module_code)) {
            $unit = CompetencyUnit::query()
                ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
                ->where('code', $module->module_code)
                ->first();
        }

        if (! $unit
            && $module->competency_category === TrainingModule::CATEGORY_CUSTOM
            && $module->requiresEvaluation()) {
            $unit = $this->createCustomUnit($module, $customOutcomeTitles);
        }

        if ($unit && (int) $module->competency_unit_id !== (int) $unit->id) {
            $module->forceFill(['competency_unit_id' => $unit->id])->save();
        }

        if ($unit) {
            $unit->loadMissing('outcomes');
            foreach ($unit->outcomes as $index => $outcome) {
                TrainingSubmodule::query()->updateOrCreate(
                    [
                        'training_module_id' => $module->id,
                        'competency_outcome_id' => $outcome->id,
                    ],
                    [
                        'title' => $outcome->title,
                        'position' => $index + 1,
                        'is_required' => $outcome->is_required,
                    ],
                );
            }
        }

        if (! $module->submodules()->exists()) {
            $module->submodules()->create([
                'title' => filled($module->topic) ? $module->topic : $module->title,
                'position' => 1,
                'is_required' => $module->requiresEvaluation(),
            ]);
        }

        return $module->submodules()->get();
    }

    public function assignProgress(ModuleProgress $parentProgress): void
    {
        $module = $parentProgress->module()->firstOrFail();
        $submodules = $this->ensureStructure($module);

        foreach ($submodules as $submodule) {
            TrainingSubmoduleProgress::query()->firstOrCreate([
                'enrollment_application_id' => $parentProgress->enrollment_application_id,
                'training_submodule_id' => $submodule->id,
            ], [
                'status' => TrainingSubmoduleProgress::STATUS_NOT_STARTED,
                'progress_percent' => 0,
            ]);
        }
    }

    public function recalculateParent(EnrollmentApplication $application, TrainingModule $module): ModuleProgress
    {
        $parent = ModuleProgress::query()->where([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
        ])->lockForUpdate()->firstOrFail();
        $submodules = $module->submodules()->where('is_required', true)->get();
        $childProgress = TrainingSubmoduleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->whereIn('training_submodule_id', $submodules->pluck('id'))
            ->get();

        if ($submodules->isEmpty()) {
            return $parent;
        }

        $bySubmodule = $childProgress->keyBy('training_submodule_id');
        $allCompleted = $submodules->every(
            fn (TrainingSubmodule $submodule): bool => $bySubmodule->get($submodule->id)?->isTrainerValidated() ?? false
        );
        $hasRemediation = $childProgress->contains(
            fn (TrainingSubmoduleProgress $progress): bool => $progress->status === TrainingSubmoduleProgress::STATUS_NEEDS_REMEDIATION
        );
        $allSubmittedOrCompleted = $submodules->every(function (TrainingSubmodule $submodule) use ($bySubmodule): bool {
            $status = $bySubmodule->get($submodule->id)?->status;

            return in_array($status, [
                TrainingSubmoduleProgress::STATUS_AWAITING_EVALUATION,
                TrainingSubmoduleProgress::STATUS_COMPLETED,
            ], true);
        });
        $hasStarted = $childProgress->contains(
            fn (TrainingSubmoduleProgress $progress): bool => $progress->status !== TrainingSubmoduleProgress::STATUS_NOT_STARTED
        );
        $percent = (int) round($submodules->average(function (TrainingSubmodule $submodule) use ($bySubmodule): int {
            return (int) ($bySubmodule->get($submodule->id)?->progress_percent ?? 0);
        }));
        $latestEvaluation = $childProgress->sortByDesc('evaluated_at')->first();
        $allPracticalCompetent = $childProgress->count() === $submodules->count()
            && $childProgress->every(
                fn (TrainingSubmoduleProgress $progress): bool => $progress->practical_rating === ModuleProgress::RATING_COMPETENT
            );
        $hasPracticalRemediation = $childProgress->contains(
            fn (TrainingSubmoduleProgress $progress): bool => $progress->practical_rating === ModuleProgress::RATING_NOT_YET_COMPETENT
        );

        $status = match (true) {
            $allCompleted => ModuleProgress::STATUS_COMPLETED,
            $hasRemediation => ModuleProgress::STATUS_NEEDS_REMEDIATION,
            $allSubmittedOrCompleted => ModuleProgress::STATUS_AWAITING_EVALUATION,
            $hasStarted => ModuleProgress::STATUS_IN_PROGRESS,
            default => ModuleProgress::STATUS_NOT_STARTED,
        };

        $parent->forceFill([
            'status' => $status,
            'progress_percent' => $allCompleted ? 100 : min($percent, 99),
            'submitted_at' => $allSubmittedOrCompleted
                ? ($childProgress->max('submitted_at') ?: $parent->submitted_at)
                : null,
            'quiz_score' => $childProgress->whereNotNull('quiz_score')->isNotEmpty()
                ? round((float) $childProgress->whereNotNull('quiz_score')->avg('quiz_score'), 2)
                : null,
            'practical_rating' => match (true) {
                $allPracticalCompetent => ModuleProgress::RATING_COMPETENT,
                $hasPracticalRemediation => ModuleProgress::RATING_NOT_YET_COMPETENT,
                default => ModuleProgress::RATING_PENDING,
            },
            'competency_outcome' => match (true) {
                $allCompleted => ModuleProgress::OUTCOME_COMPETENT,
                $hasRemediation => ModuleProgress::OUTCOME_NOT_YET_COMPETENT,
                default => ModuleProgress::OUTCOME_IN_PROGRESS,
            },
            'evaluated_by_id' => $latestEvaluation?->evaluated_by_id,
            'evaluated_at' => $latestEvaluation?->evaluated_at,
            'completed_at' => $allCompleted ? ($parent->completed_at ?: now()) : null,
        ])->save();

        return $parent;
    }

    public function syncCompetencyOutcome(
        EnrollmentApplication $application,
        TrainingModule $module,
        TrainingSubmodule $submodule,
        TrainingSubmoduleProgress $progress,
        User $assessor,
    ): void {
        if (! $module->competency_unit_id || ! $submodule->competency_outcome_id) {
            return;
        }

        $unit = CompetencyUnit::query()->with('outcomes')->findOrFail($module->competency_unit_id);
        $record = TraineeCompetencyRecord::query()->firstOrNew([
            'enrollment_application_id' => $application->id,
            'competency_unit_id' => $unit->id,
        ]);
        $childStatus = match ($progress->competency_outcome) {
            ModuleProgress::OUTCOME_COMPETENT => TraineeCompetencyRecord::STATUS_COMPETENT,
            ModuleProgress::OUTCOME_NOT_YET_COMPETENT => TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT,
            default => TraineeCompetencyRecord::STATUS_IN_PROGRESS,
        };

        $record->fill([
            'status' => $record->status ?: TraineeCompetencyRecord::STATUS_IN_PROGRESS,
            'notes' => $progress->evaluation_remarks ?? $record->notes,
            'assessed_by_id' => $assessor->id,
            'assessed_at' => now(),
        ])->save();
        $record->outcomeResults()->updateOrCreate(
            ['competency_outcome_id' => $submodule->competency_outcome_id],
            [
                'training_module_id' => $module->id,
                'status' => $childStatus,
                'assessed_by_id' => $assessor->id,
                'assessed_at' => now(),
            ],
        );

        $requiredOutcomeIds = $unit->outcomes->where('is_required', true)->pluck('id');
        $results = $record->outcomeResults()
            ->whereIn('competency_outcome_id', $requiredOutcomeIds)
            ->get();
        $allCompetent = $requiredOutcomeIds->isNotEmpty()
            && $results->count() === $requiredOutcomeIds->count()
            && $results->every(fn ($result): bool => $result->status === TraineeCompetencyRecord::STATUS_COMPETENT);
        $hasNyc = $results->contains('status', TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT);

        $record->forceFill([
            'status' => match (true) {
                $allCompetent => TraineeCompetencyRecord::STATUS_COMPETENT,
                $hasNyc => TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT,
                default => TraineeCompetencyRecord::STATUS_IN_PROGRESS,
            },
            'assessed_by_id' => $assessor->id,
            'assessed_at' => now(),
        ])->save();
    }

    /**
     * Earlier required outcomes marked Not yet competent block later classwork
     * until those outcomes are Competent. The failed outcome itself stays open
     * so the trainee can remediate it.
     *
     * @param  Collection<int, TrainingSubmodule>  $submodules
     * @param  Collection<int, TrainingSubmoduleProgress>  $progressById
     */
    public function nycBlockerFor(
        TrainingSubmodule $target,
        Collection $submodules,
        Collection $progressById,
    ): ?TrainingSubmodule {
        $required = $submodules
            ->filter(fn (TrainingSubmodule $submodule): bool => $submodule->is_required)
            ->sortBy(fn (TrainingSubmodule $submodule): array => [
                (int) $submodule->position,
                (int) $submodule->id,
            ])
            ->values();

        foreach ($required as $candidate) {
            if ((int) $candidate->id === (int) $target->id) {
                return null;
            }

            if ((int) $candidate->position > (int) $target->position) {
                return null;
            }

            if ($progressById->get($candidate->id)?->needsRemediation()) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, TrainingSubmodule>  $submodules
     * @param  Collection<int, TrainingSubmoduleProgress>  $progressById
     * @return array{can_work: bool, blocker: ?TrainingSubmodule}
     */
    public function accessForSubmodule(
        TrainingSubmodule $target,
        Collection $submodules,
        Collection $progressById,
    ): array {
        $progress = $progressById->get($target->id);
        $blocker = $this->nycBlockerFor($target, $submodules, $progressById);
        $canWork = $progress?->needsRemediation() || $blocker === null;

        return [
            'can_work' => $canWork,
            'blocker' => $canWork ? null : $blocker,
        ];
    }

    public function assertTraineeCanWorkOnSubmodule(
        EnrollmentApplication $application,
        TrainingModule $module,
        TrainingSubmodule $submodule,
        string $field = 'action',
    ): void {
        $module->loadMissing('submodules');
        $progressById = TrainingSubmoduleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->whereIn('training_submodule_id', $module->submodules->pluck('id'))
            ->get()
            ->keyBy('training_submodule_id');
        $access = $this->accessForSubmodule($submodule, $module->submodules, $progressById);

        if ($access['can_work']) {
            return;
        }

        $blocker = $access['blocker'];
        $label = $blocker?->title ?: 'the previous submodule';

        throw ValidationException::withMessages([
            $field => "{$label} is Not yet competent. Remediate that outcome before moving to {$submodule->title}. The next classwork module stays locked until this unit is Competent.",
        ]);
    }

    public function traineeCanWorkOnSubmodule(
        EnrollmentApplication $application,
        TrainingModule $module,
        TrainingSubmodule $submodule,
    ): bool {
        $module->loadMissing('submodules');
        $progressById = TrainingSubmoduleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->whereIn('training_submodule_id', $module->submodules->pluck('id'))
            ->get()
            ->keyBy('training_submodule_id');

        return $this->accessForSubmodule($submodule, $module->submodules, $progressById)['can_work'];
    }

    /** @param list<string> $outcomeTitles */
    private function createCustomUnit(TrainingModule $module, array $outcomeTitles): CompetencyUnit
    {
        $titles = collect($outcomeTitles)
            ->map(fn ($title) => trim((string) $title))
            ->filter()
            ->unique(fn ($title) => mb_strtolower($title))
            ->values();
        if ($titles->isEmpty()) {
            $titles = collect([filled($module->topic) ? $module->topic : $module->title]);
        }

        return DB::transaction(function () use ($module, $titles): CompetencyUnit {
            $existing = $module->fresh()->competencyUnit;
            if ($existing) {
                return $existing;
            }

            $title = trim($module->title);
            if (CompetencyUnit::query()
                ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
                ->where('title', $title)
                ->exists()) {
                $title .= ' (Custom '.$module->id.')';
            }

            $sortOrder = ((int) CompetencyUnit::query()
                ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
                ->lockForUpdate()
                ->max('sort_order')) + 1;
            $unit = CompetencyUnit::query()->create([
                'program_code' => CaregivingNcIiCatalog::PROGRAM_CODE,
                'category' => TrainingModule::CATEGORY_CUSTOM,
                'code' => filled($module->module_code) ? $module->module_code : 'MCARE-CUSTOM-'.$module->id,
                'title' => $title,
                'sort_order' => $sortOrder,
                'is_required' => false,
                'is_tor_included' => false,
            ]);

            foreach ($titles as $index => $outcomeTitle) {
                $unit->outcomes()->create([
                    'title' => $outcomeTitle,
                    'sort_order' => $index + 1,
                    'is_required' => true,
                ]);
            }

            $module->forceFill(['competency_unit_id' => $unit->id])->save();

            return $unit;
        }, 3);
    }
}
