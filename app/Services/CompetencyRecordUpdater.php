<?php

namespace App\Services;

use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\TraineeCompetencyRecord;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CompetencyRecordUpdater
{
    public function __construct(private readonly TorGradeScale $gradeScale) {}

    /**
     * Persist one trainee/unit assessment inside the caller's transaction.
     *
     * @param  array{status: string, percentage_score?: mixed, notes?: ?string, outcomes: array<int|string, string>}  $payload
     */
    public function save(
        EnrollmentApplication $application,
        CompetencyUnit $unit,
        array $payload,
        User $assessor,
    ): TraineeCompetencyRecord {
        $record = TraineeCompetencyRecord::query()
            ->where('enrollment_application_id', $application->id)
            ->where('competency_unit_id', $unit->id)
            ->lockForUpdate()
            ->first() ?? new TraineeCompetencyRecord([
                'enrollment_application_id' => $application->id,
                'competency_unit_id' => $unit->id,
            ]);

        if ($record->exists && $record->locked_at) {
            throw ValidationException::withMessages([
                'records' => "{$application->first_name} {$application->last_name}: {$unit->title} is locked because an official document was generated.",
            ]);
        }

        $outcomeStatuses = collect($payload['outcomes']);
        $expectedOutcomeIds = $unit->outcomes->pluck('id')->map(fn ($id) => (string) $id);
        $submittedOutcomeIds = $outcomeStatuses->keys()->map(fn ($id) => (string) $id);

        if ($expectedOutcomeIds->sort()->values()->all()
            !== $submittedOutcomeIds->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'records' => "The submitted outcomes for {$unit->title} are incomplete.",
            ]);
        }

        $score = filled($payload['percentage_score'] ?? null)
            ? (float) $payload['percentage_score']
            : null;

        if ($payload['status'] === TraineeCompetencyRecord::STATUS_NOT_ASSESSED) {
            $score = null;
        }

        if ($payload['status'] === TraineeCompetencyRecord::STATUS_COMPETENT
            && ($score === null || $score < 75
                || $outcomeStatuses->contains(
                    fn ($status) => $status !== TraineeCompetencyRecord::STATUS_COMPETENT
                ))) {
            throw ValidationException::withMessages([
                'records' => "{$unit->title} needs a score of at least 75 and every outcome marked competent.",
            ]);
        }

        $assessed = $payload['status'] !== TraineeCompetencyRecord::STATUS_NOT_ASSESSED;
        $attributes = [
            'status' => $payload['status'],
            'percentage_score' => $score,
            'tor_grade' => $payload['status'] === TraineeCompetencyRecord::STATUS_COMPETENT
                ? $this->gradeScale->fromPercentage($score)
                : null,
            'assessed_by_id' => $assessed ? $assessor->id : null,
            'assessed_at' => $assessed ? now() : null,
        ];

        // Bulk updates preserve existing notes unless the trainer supplied a batch note.
        if (array_key_exists('notes', $payload)) {
            $attributes['notes'] = $payload['notes'];
        }

        $record->fill($attributes)->save();

        foreach ($unit->outcomes as $outcome) {
            $status = $outcomeStatuses->get((string) $outcome->id);
            $record->outcomeResults()->updateOrCreate(
                ['competency_outcome_id' => $outcome->id],
                [
                    // Manual trainer records are shared unit/outcome records,
                    // not attributable to one learning module.
                    'training_module_id' => null,
                    'status' => $status,
                    'assessed_by_id' => $status === TraineeCompetencyRecord::STATUS_NOT_ASSESSED
                        ? null
                        : $assessor->id,
                    'assessed_at' => $status === TraineeCompetencyRecord::STATUS_NOT_ASSESSED
                        ? null
                        : now(),
                ],
            );
        }

        return $record;
    }
}
