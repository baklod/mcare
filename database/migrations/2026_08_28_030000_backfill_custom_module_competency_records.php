<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $evaluations = DB::table('training_submodule_progress as progress')
            ->join('training_submodules as submodule', 'submodule.id', '=', 'progress.training_submodule_id')
            ->join('training_modules as module', 'module.id', '=', 'submodule.training_module_id')
            ->join('competency_units as unit', 'unit.id', '=', 'module.competency_unit_id')
            ->where('module.competency_category', 'custom')
            ->where('unit.category', 'custom')
            ->whereNotNull('submodule.competency_outcome_id')
            ->whereIn('progress.competency_outcome', [
                'competent',
                'not_yet_competent',
                'in_progress',
            ])
            ->select([
                'progress.enrollment_application_id',
                'progress.competency_outcome as result_status',
                'progress.evaluation_remarks',
                'progress.evaluated_by_id',
                'progress.evaluated_at',
                'module.competency_unit_id',
                'submodule.competency_outcome_id',
            ])
            ->orderBy('progress.id')
            ->get();

        $recordIds = collect();

        foreach ($evaluations as $evaluation) {
            $record = DB::table('trainee_competency_records')
                ->where('enrollment_application_id', $evaluation->enrollment_application_id)
                ->where('competency_unit_id', $evaluation->competency_unit_id)
                ->first();

            if (! $record) {
                $recordId = DB::table('trainee_competency_records')->insertGetId([
                    'enrollment_application_id' => $evaluation->enrollment_application_id,
                    'competency_unit_id' => $evaluation->competency_unit_id,
                    'status' => 'in_progress',
                    'percentage_score' => null,
                    'tor_grade' => null,
                    'notes' => $evaluation->evaluation_remarks,
                    'assessed_by_id' => $evaluation->evaluated_by_id,
                    'assessed_at' => $evaluation->evaluated_at,
                    'locked_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $recordId = $record->id;
                DB::table('trainee_competency_records')->where('id', $recordId)->update([
                    'notes' => $evaluation->evaluation_remarks ?: $record->notes,
                    'assessed_by_id' => $evaluation->evaluated_by_id ?: $record->assessed_by_id,
                    'assessed_at' => $evaluation->evaluated_at ?: $record->assessed_at,
                    'updated_at' => $now,
                ]);
            }

            $outcomeKey = [
                'trainee_competency_record_id' => $recordId,
                'competency_outcome_id' => $evaluation->competency_outcome_id,
            ];
            $outcomeValues = [
                'status' => $evaluation->result_status,
                'assessed_by_id' => $evaluation->evaluated_by_id,
                'assessed_at' => $evaluation->evaluated_at,
                'updated_at' => $now,
            ];
            if (! DB::table('trainee_outcome_results')->where($outcomeKey)->exists()) {
                $outcomeValues['created_at'] = $now;
            }
            DB::table('trainee_outcome_results')->updateOrInsert($outcomeKey, $outcomeValues);

            $recordIds->push($recordId);
        }

        foreach ($recordIds->unique() as $recordId) {
            $record = DB::table('trainee_competency_records')->where('id', $recordId)->first();
            $requiredOutcomeIds = DB::table('competency_outcomes')
                ->where('competency_unit_id', $record->competency_unit_id)
                ->where('is_required', true)
                ->pluck('id');
            $results = DB::table('trainee_outcome_results')
                ->where('trainee_competency_record_id', $recordId)
                ->whereIn('competency_outcome_id', $requiredOutcomeIds)
                ->get();
            $allCompetent = $requiredOutcomeIds->isNotEmpty()
                && $results->count() === $requiredOutcomeIds->count()
                && $results->every(fn ($result): bool => $result->status === 'competent');
            $hasRemediation = $results->contains('status', 'not_yet_competent');

            DB::table('trainee_competency_records')->where('id', $recordId)->update([
                'status' => match (true) {
                    $allCompetent => 'competent',
                    $hasRemediation => 'not_yet_competent',
                    default => 'in_progress',
                },
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Preserve assessor-authored and migrated competency history.
    }
};
