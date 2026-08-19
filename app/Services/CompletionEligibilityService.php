<?php

namespace App\Services;

use App\Models\CompetencyOutcome;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TraineeCompetencyRecord;
use App\Models\TraineeOutcomeResult;
use App\Models\TrainingModule;
use App\Support\CaregivingNcIiCatalog;

class CompletionEligibilityService
{
    /**
     * @return array{
     *   eligible: bool,
     *   checks: array<string, array{passed: bool, label: string, detail: string}>,
     *   counts: array<string, int>
     * }
     */
    public function evaluate(EnrollmentApplication $application): array
    {
        $requiredUnits = CompetencyUnit::query()
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->where('is_required', true);
        $requiredUnitIds = (clone $requiredUnits)->pluck('id');
        $requiredOutcomeIds = CompetencyOutcome::query()
            ->whereIn('competency_unit_id', $requiredUnitIds)
            ->where('is_required', true)
            ->pluck('id');

        $competentRecords = TraineeCompetencyRecord::query()
            ->where('enrollment_application_id', $application->id)
            ->whereIn('competency_unit_id', $requiredUnitIds)
            ->where('status', TraineeCompetencyRecord::STATUS_COMPETENT)
            ->count();
        $competentOutcomes = TraineeOutcomeResult::query()
            ->whereHas('record', fn ($query) => $query
                ->where('enrollment_application_id', $application->id))
            ->whereIn('competency_outcome_id', $requiredOutcomeIds)
            ->where('status', TraineeCompetencyRecord::STATUS_COMPETENT)
            ->count();

        $modules = $this->moduleQuery($application)->pluck('id');
        $completedModules = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->whereIn('training_module_id', $modules)
            ->where('status', ModuleProgress::STATUS_COMPLETED)
            ->count();

        $quizzes = $this->quizQuery($application)->pluck('id');
        $passedQuizzes = $quizzes->filter(fn ($quizId) => QuizAttempt::query()
            ->where('quiz_id', $quizId)
            ->where('enrollment_application_id', $application->id)
            ->where('status', QuizAttempt::STATUS_GRADED)
            ->where('passed', true)
            ->exists())->count();

        $unitCount = $requiredUnitIds->count();
        $outcomeCount = $requiredOutcomeIds->count();
        $moduleCount = $modules->count();
        $quizCount = $quizzes->count();

        $checks = [
            'approved' => $this->check(
                $application->status === EnrollmentApplication::STATUS_APPROVED,
                'Enrollment approved',
                $application->statusLabel(),
            ),
            'payment' => $this->check(
                $application->payment_status === EnrollmentApplication::PAYMENT_PAID,
                'Payment cleared',
                $application->paymentStatusLabel(),
            ),
            'batch' => $this->check(
                $application->batch?->trainingState() === 'completed',
                'Training period completed',
                $application->batch?->trainingStateLabel() ?? 'No batch assigned',
            ),
            'units' => $this->check(
                $unitCount > 0 && $competentRecords === $unitCount,
                'Competency units completed',
                "{$competentRecords} of {$unitCount} competent",
            ),
            'outcomes' => $this->check(
                $outcomeCount > 0 && $competentOutcomes === $outcomeCount,
                'Achievement outcomes completed',
                "{$competentOutcomes} of {$outcomeCount} competent",
            ),
            'modules' => $this->check(
                $completedModules === $moduleCount,
                'Required learning modules completed',
                "{$completedModules} of {$moduleCount} complete",
            ),
            'quizzes' => $this->check(
                $passedQuizzes === $quizCount,
                'Published quizzes passed',
                "{$passedQuizzes} of {$quizCount} passed",
            ),
        ];

        return [
            'eligible' => collect($checks)->every(fn ($check) => $check['passed']),
            'checks' => $checks,
            'counts' => [
                'units' => $unitCount,
                'competent_units' => $competentRecords,
                'outcomes' => $outcomeCount,
                'competent_outcomes' => $competentOutcomes,
                'modules' => $moduleCount,
                'completed_modules' => $completedModules,
                'quizzes' => $quizCount,
                'passed_quizzes' => $passedQuizzes,
            ],
        ];
    }

    private function moduleQuery(EnrollmentApplication $application)
    {
        return TrainingModule::query()
            ->where('is_published', true)
            ->where(function ($query) use ($application) {
                $query->where('target_enrollment_application_id', $application->id)
                    ->orWhere(function ($batchQuery) use ($application) {
                        $batchQuery->whereNull('target_enrollment_application_id')
                            ->where(fn ($scopeQuery) => $scopeQuery
                                ->whereNull('training_batch_id')
                                ->orWhere('training_batch_id', $application->training_batch_id));
                    });
            });
    }

    private function quizQuery(EnrollmentApplication $application)
    {
        return Quiz::query()
            ->where('is_published', true)
            ->where(function ($query) use ($application) {
                $query->where('target_enrollment_application_id', $application->id)
                    ->orWhere(function ($batchQuery) use ($application) {
                        $batchQuery->whereNull('target_enrollment_application_id')
                            ->where(fn ($scopeQuery) => $scopeQuery
                                ->whereNull('training_batch_id')
                                ->orWhere('training_batch_id', $application->training_batch_id));
                    });
            });
    }

    /** @return array{passed: bool, label: string, detail: string} */
    private function check(bool $passed, string $label, string $detail): array
    {
        return compact('passed', 'label', 'detail');
    }
}
