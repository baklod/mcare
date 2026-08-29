<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TrainingModule;
use App\Models\TrainingSubmodule;
use App\Models\TrainingSubmoduleProgress;
use Illuminate\Support\Collection;

class ModuleAssessmentService
{
    /**
     * @return array{
     *   quizzes: Collection<int, Quiz>,
     *   required_count: int,
     *   passed_count: int,
     *   resolved_count: int,
     *   exhausted_failed_count: int,
     *   incomplete_count: int,
     *   average_score: float|null,
     *   all_passed: bool,
     *   ready_for_remediation_evaluation: bool
     * }
     */
    public function summary(
        TrainingModule $module,
        EnrollmentApplication $application,
        ?TrainingSubmodule $submodule = null,
    ): array
    {
        $quizzes = $module->quizzes()
            ->when($submodule, function ($query) use ($submodule): void {
                $query->where(function ($scoped) use ($submodule): void {
                    // Older module-wide quizzes have no child assignment. Keep
                    // them active for every child so existing classwork still
                    // gates the new submodule workflow.
                    $scoped->where('training_submodule_id', $submodule->id)
                        ->orWhereNull('training_submodule_id');
                });
            })
            ->released()
            ->with(['attempts' => fn ($query) => $query
                ->where('enrollment_application_id', $application->id)
                ->where('status', QuizAttempt::STATUS_GRADED)
                ->latest('attempt_number')])
            ->get();

        $scores = collect();
        $passedCount = 0;
        $resolvedCount = 0;
        $exhaustedFailedCount = 0;

        foreach ($quizzes as $quiz) {
            $attempts = $quiz->attempts;
            $bestAttempt = $attempts->sortByDesc('score_percent')->first();
            $passed = $attempts->contains('passed', true);
            $exhausted = ! $passed && $attempts->count() >= (int) $quiz->attempt_limit;

            if ($bestAttempt?->score_percent !== null) {
                $scores->push((float) $bestAttempt->score_percent);
            }

            if ($passed) {
                $passedCount++;
                $resolvedCount++;
            } elseif ($exhausted) {
                $exhaustedFailedCount++;
                $resolvedCount++;
            }
        }

        $requiredCount = $quizzes->count();
        $allPassed = $requiredCount > 0 && $passedCount === $requiredCount;

        return [
            'quizzes' => $quizzes,
            'required_count' => $requiredCount,
            'passed_count' => $passedCount,
            'resolved_count' => $resolvedCount,
            'exhausted_failed_count' => $exhaustedFailedCount,
            'incomplete_count' => max(0, $requiredCount - $resolvedCount),
            'average_score' => $scores->isEmpty() ? null : round((float) $scores->average(), 2),
            'all_passed' => $allPassed,
            'ready_for_remediation_evaluation' => $requiredCount > 0
                && $resolvedCount === $requiredCount
                && $exhaustedFailedCount > 0,
        ];
    }

    public function resetPendingProgressForPublishedQuiz(Quiz $quiz): int
    {
        if (! $quiz->training_module_id) {
            return 0;
        }

        if ($quiz->training_submodule_id) {
            $applicationIds = TrainingSubmoduleProgress::query()
                ->where('training_submodule_id', $quiz->training_submodule_id)
                ->whereIn('status', [
                    TrainingSubmoduleProgress::STATUS_AWAITING_EVALUATION,
                    TrainingSubmoduleProgress::STATUS_NEEDS_REMEDIATION,
                ])
                ->pluck('enrollment_application_id');
            $updated = TrainingSubmoduleProgress::query()
                ->where('training_submodule_id', $quiz->training_submodule_id)
                ->whereIn('enrollment_application_id', $applicationIds)
                ->update([
                    'status' => TrainingSubmoduleProgress::STATUS_IN_PROGRESS,
                    'progress_percent' => 50,
                    'submitted_at' => null,
                    'completed_at' => null,
                    'quiz_score' => null,
                    'practical_rating' => ModuleProgress::RATING_PENDING,
                    'competency_outcome' => ModuleProgress::OUTCOME_IN_PROGRESS,
                    'evaluation_remarks' => null,
                    'evaluated_by_id' => null,
                    'evaluated_at' => null,
                    'updated_at' => now(),
                ]);

            ModuleProgress::query()
                ->where('training_module_id', $quiz->training_module_id)
                ->whereIn('enrollment_application_id', $applicationIds)
                ->where('status', '!=', ModuleProgress::STATUS_COMPLETED)
                ->update([
                    'status' => ModuleProgress::STATUS_IN_PROGRESS,
                    'progress_percent' => 50,
                    'submitted_at' => null,
                    'completed_at' => null,
                    'competency_outcome' => ModuleProgress::OUTCOME_IN_PROGRESS,
                    'updated_at' => now(),
                ]);

            return $updated;
        }

        return ModuleProgress::query()
            ->where('training_module_id', $quiz->training_module_id)
            ->whereIn('status', [
                ModuleProgress::STATUS_AWAITING_EVALUATION,
                ModuleProgress::STATUS_NEEDS_REMEDIATION,
            ])
            ->where(function ($query): void {
                $query->where('status', '!=', ModuleProgress::STATUS_COMPLETED)
                    ->orWhere('competency_outcome', '!=', ModuleProgress::OUTCOME_COMPETENT)
                    ->orWhereNull('competency_outcome');
            })
            ->when(
                $quiz->target_enrollment_application_id,
                fn ($query, $applicationId) => $query->where('enrollment_application_id', $applicationId),
            )
            ->update([
                'status' => ModuleProgress::STATUS_IN_PROGRESS,
                'progress_percent' => 50,
                'submitted_at' => null,
                'completed_at' => null,
                'quiz_score' => null,
                'practical_rating' => ModuleProgress::RATING_PENDING,
                'competency_outcome' => ModuleProgress::OUTCOME_IN_PROGRESS,
                'evaluation_remarks' => null,
                'evaluated_by_id' => null,
                'evaluated_at' => null,
                'updated_at' => now(),
            ]);
    }
}
