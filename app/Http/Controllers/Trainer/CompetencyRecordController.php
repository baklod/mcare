<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Services\CompetencyRecordUpdater;
use App\Support\CaregivingNcIiCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompetencyRecordController extends Controller
{
    public function index(Request $request): View
    {
        $assignedBatch = TrainingBatch::assignedTo($request->user());
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
        ]);
        $batches = $assignedBatch ? collect([$assignedBatch]) : collect();
        $requestedBatchId = isset($validated['batch_id']) ? (int) $validated['batch_id'] : null;
        $this->assertBatchAccess($request, $requestedBatchId);
        $selectedBatchId = $requestedBatchId ?? $assignedBatch?->id;
        $units = $this->unitsForBatch($selectedBatchId);

        $trainees = collect();
        $traineeLimitReached = false;

        if ($selectedBatchId) {
            $trainees = EnrollmentApplication::query()
                ->with(['batch', 'user', 'competencyRecords.outcomeResults'])
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->where('training_batch_id', $selectedBatchId)
                ->when($validated['schedule'] ?? null, fn ($query, $schedule) => $query
                    ->where('schedule_preference', $schedule))
                ->when(trim((string) ($validated['search'] ?? '')), function ($query, $search) {
                    $query->where(fn ($nested) => $nested
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(101)
                ->get();

            $traineeLimitReached = $trainees->count() > 100;
            $trainees = $trainees->take(100)->values();
        }

        $recordsByTrainee = $trainees->mapWithKeys(fn ($trainee) => [
            $trainee->id => $trainee->competencyRecords->keyBy('competency_unit_id'),
        ]);
        $requiredUnits = $units->where('is_required', true);
        $competentMarks = $trainees->sum(fn ($trainee) => $trainee->competencyRecords
            ->whereIn('competency_unit_id', $requiredUnits->pluck('id'))
            ->where('status', TraineeCompetencyRecord::STATUS_COMPETENT)
            ->count());
        $possibleMarks = $trainees->count() * $requiredUnits->count();

        return view('trainer.competencies.index', [
            'trainees' => $trainees,
            'traineeLimitReached' => $traineeLimitReached,
            'unitsByCategory' => $units->groupBy('category'),
            'recordsByTrainee' => $recordsByTrainee,
            'statuses' => TraineeCompetencyRecord::statuses(),
            'filters' => array_merge($validated, ['batch_id' => $selectedBatchId]),
            'batches' => $batches,
            'selectedBatch' => $batches->firstWhere('id', $selectedBatchId),
            'summary' => [
                'trainees' => $trainees->count(),
                'competent' => $competentMarks,
                'possible' => $possibleMarks,
                'percent' => $possibleMarks > 0
                    ? (int) round(($competentMarks / $possibleMarks) * 100)
                    : 0,
            ],
        ]);
    }

    public function edit(Request $request, EnrollmentApplication $enrollmentApplication): View
    {
        $this->assertApproved($enrollmentApplication);
        $this->assertBatchAccess($request, (int) $enrollmentApplication->training_batch_id);
        $enrollmentApplication->load(['batch', 'user', 'competencyRecords.outcomeResults']);

        return view('trainer.competencies.edit', [
            'trainee' => $enrollmentApplication,
            'unitsByCategory' => $this->unitsForBatch((int) $enrollmentApplication->training_batch_id)
                ->groupBy('category'),
            'recordsByUnit' => $enrollmentApplication->competencyRecords->keyBy('competency_unit_id'),
            'statuses' => TraineeCompetencyRecord::statuses(),
        ]);
    }

    public function chart(Request $request, TrainingBatch $trainingBatch, string $chart): View
    {
        abort_unless(in_array($chart, ['progress', 'achievement'], true), 404);
        $this->assertBatchAccess($request, (int) $trainingBatch->id);

        $validated = $request->validate([
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
        ]);
        $units = $this->unitsForBatch((int) $trainingBatch->id);
        $trainees = $trainingBatch->applications()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when($validated['schedule'] ?? null, fn ($query, $schedule) => $query
                ->where('schedule_preference', $schedule))
            ->with(['user', 'competencyRecords.outcomeResults'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Build a lookup once so the wide chart does not repeatedly scan each trainee's records.
        $recordsByTrainee = $trainees->mapWithKeys(fn ($trainee) => [
            $trainee->id => $trainee->competencyRecords->keyBy('competency_unit_id'),
        ]);

        return view('trainer.competencies.chart', [
            'batch' => $trainingBatch,
            'chart' => $chart,
            'schedule' => $validated['schedule'] ?? null,
            'trainees' => $trainees,
            'unitsByCategory' => $units->groupBy('category'),
            'recordsByTrainee' => $recordsByTrainee,
        ]);
    }

    public function update(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        CompetencyRecordUpdater $updater,
    ): RedirectResponse {
        $this->assertApproved($enrollmentApplication);
        $this->assertBatchAccess($request, (int) $enrollmentApplication->training_batch_id);
        $statuses = array_keys(TraineeCompetencyRecord::statuses());
        $validated = $request->validate([
            'records' => ['required', 'array'],
            'records.*.unit_id' => ['required', 'integer', 'distinct', 'exists:competency_units,id'],
            'records.*.status' => ['required', Rule::in($statuses)],
            'records.*.percentage_score' => ['nullable', 'numeric', 'between:0,100'],
            'records.*.notes' => ['nullable', 'string', 'max:1000'],
            'records.*.outcomes' => ['required', 'array'],
            'records.*.outcomes.*' => ['required', Rule::in($statuses)],
        ]);

        $units = CompetencyUnit::query()
            ->with('outcomes')
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->whereIn('id', collect($validated['records'])->pluck('unit_id'))
            ->where(function ($query) use ($enrollmentApplication): void {
                $query->where('is_required', true)
                    ->orWhereHas('trainingModules', fn ($modules) => $modules
                        ->where('training_batch_id', $enrollmentApplication->training_batch_id)
                        ->where('is_published', true));
            })
            ->get()
            ->keyBy('id');

        if ($units->count() !== count($validated['records'])) {
            throw ValidationException::withMessages([
                'records' => 'One or more competency units are not part of Caregiving NC II.',
            ]);
        }

        DB::transaction(function () use (
            $request,
            $enrollmentApplication,
            $validated,
            $units,
            $updater,
        ): void {
            foreach ($validated['records'] as $payload) {
                $unit = $units->get((int) $payload['unit_id']);
                $updater->save($enrollmentApplication, $unit, $payload, $request->user());
            }
        });

        AdminActivityLog::record($request->user(), 'trainer.competency-records.updated', $enrollmentApplication, [
            'trainee' => trim("{$enrollmentApplication->first_name} {$enrollmentApplication->last_name}"),
            'units_submitted' => count($validated['records']),
        ]);

        return back()->with('saved', 'Competency record updated. Progress and achievement views now use these results.');
    }

    public function bulkUpdate(Request $request, CompetencyRecordUpdater $updater): RedirectResponse
    {
        $statuses = array_keys(TraineeCompetencyRecord::statuses());
        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'unit_id' => ['required', 'integer', 'exists:competency_units,id'],
            'trainee_ids' => ['required', 'array', 'min:1', 'max:100'],
            'trainee_ids.*' => ['required', 'integer', 'distinct', 'exists:enrollment_applications,id'],
            'status' => ['required', Rule::in($statuses)],
            'percentage_score' => ['nullable', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->assertBatchAccess($request, (int) $validated['batch_id']);

        $unit = CompetencyUnit::query()
            ->with('outcomes')
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->where(function ($query) use ($validated): void {
                $query->where('is_required', true)
                    ->orWhereHas('trainingModules', fn ($modules) => $modules
                        ->where('training_batch_id', $validated['batch_id'])
                        ->where('is_published', true));
            })
            ->find($validated['unit_id']);

        if (! $unit) {
            throw ValidationException::withMessages([
                'unit_id' => 'The selected competency is not part of Caregiving NC II.',
            ]);
        }

        if ($validated['status'] === TraineeCompetencyRecord::STATUS_COMPETENT
            && (! filled($validated['percentage_score'] ?? null)
                || (float) $validated['percentage_score'] < 75)) {
            throw ValidationException::withMessages([
                'percentage_score' => 'A bulk Competent update needs a shared score from 75 to 100.',
            ]);
        }

        $traineeIds = collect($validated['trainee_ids'])->map(fn ($id) => (int) $id)->values();

        DB::transaction(function () use ($request, $validated, $unit, $traineeIds, $updater): void {
            $trainees = EnrollmentApplication::query()
                ->whereIn('id', $traineeIds)
                ->where('training_batch_id', $validated['batch_id'])
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->lockForUpdate()
                ->get();

            // Fail the whole request before changing a record if a submitted trainee is outside the batch.
            if ($trainees->count() !== $traineeIds->count()) {
                throw ValidationException::withMessages([
                    'trainee_ids' => 'Every selected trainee must be approved and assigned to the selected batch.',
                ]);
            }

            $payload = [
                'status' => $validated['status'],
                'percentage_score' => $validated['percentage_score'] ?? null,
                'outcomes' => $unit->outcomes->mapWithKeys(
                    fn ($outcome) => [$outcome->id => $validated['status']]
                )->all(),
            ];

            if (filled($validated['notes'] ?? null)) {
                $payload['notes'] = $validated['notes'];
            }

            foreach ($trainees as $trainee) {
                $updater->save($trainee, $unit, $payload, $request->user());
            }
        });

        AdminActivityLog::record($request->user(), 'trainer.competency-records.bulk-updated', $unit, [
            'batch_id' => (int) $validated['batch_id'],
            'trainee_count' => $traineeIds->count(),
            'status' => $validated['status'],
            'percentage_score' => $validated['percentage_score'] ?? null,
        ]);

        return back()->with(
            'saved',
            "{$traineeIds->count()} trainee records were updated for {$unit->title}."
        );
    }

    private function assertApproved(EnrollmentApplication $application): void
    {
        abort_unless($application->status === EnrollmentApplication::STATUS_APPROVED, 404);
    }

    private function unitsForBatch(?int $batchId)
    {
        return CompetencyUnit::query()
            ->with('outcomes')
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->where(function ($query) use ($batchId): void {
                $query->where('is_required', true)
                    ->when($batchId, fn ($nested, $id) => $nested->orWhereHas(
                        'trainingModules',
                        fn ($modules) => $modules
                            ->where('training_batch_id', $id)
                            ->where('is_published', true)
                    ));
            })
            ->orderBy('sort_order')
            ->get();
    }

    private function assertBatchAccess(Request $request, ?int $batchId): void
    {
        if ($batchId === null) {
            return;
        }

        $assignedBatch = TrainingBatch::assignedTo($request->user());

        if (! $assignedBatch || ! $batchId || (int) $assignedBatch->id !== $batchId) {
            throw ValidationException::withMessages([
                'batch_id' => 'This trainer can only access competency records for the assigned batch.',
            ]);
        }
    }
}
