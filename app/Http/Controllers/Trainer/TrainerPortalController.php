<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Services\CompletionEligibilityService;
use App\Services\TraineeRosterCsv;
use App\Services\TrainingCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainerPortalController extends Controller
{
    public function trainings(Request $request): View
    {
        $trainer = $request->user();

        return view('trainer.trainings', [
            'batches' => TrainingBatch::query()
                ->with('trainer')
                ->withCount(['applications', 'modules'])
                ->orderByDesc('is_active')
                ->orderByDesc('year')
                ->get(),
            'assignedBatch' => TrainingBatch::assignedTo($trainer),
        ]);
    }

    public function trainees(Request $request): View
    {
        $trainer = $request->user();
        $assignedBatch = TrainingBatch::assignedTo($trainer);
        $search = trim((string) $request->query('search', ''));
        $batchId = $request->integer('batch_id') ?: null;
        $schedule = in_array($request->query('schedule'), ['AM', 'PM'], true) ? $request->query('schedule') : null;
        $this->assertBatchFilter($assignedBatch, $batchId);
        $batchId ??= $assignedBatch?->id;
        $trainees = $this->traineeRosterQuery($search, $batchId, $schedule, $assignedBatch)
            ->with(['batch', 'user', 'moduleProgress', 'paymentTransactions.recordedByAdmin', 'paymentTransactions.verifier'])
            ->paginate(15)
            ->withQueryString();

        return view('trainer.trainees', [
            'search' => $search,
            'batchId' => $batchId,
            'schedule' => $schedule,
            'batches' => $assignedBatch ? collect([$assignedBatch]) : collect(),
            'trainees' => $trainees,
            'assignedBatch' => $assignedBatch,
        ]);
    }

    public function exportTrainees(Request $request, TraineeRosterCsv $csv): StreamedResponse
    {
        $assignedBatch = TrainingBatch::assignedTo($request->user());
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
        ]);
        $requestedBatchId = isset($validated['batch_id']) ? (int) $validated['batch_id'] : null;
        $this->assertBatchFilter($assignedBatch, $requestedBatchId);
        $trainees = $this->traineeRosterQuery(
            trim((string) ($validated['search'] ?? '')),
            $requestedBatchId ?? $assignedBatch?->id,
            $validated['schedule'] ?? null,
            $assignedBatch,
        )->with(['batch', 'moduleProgress', 'paymentTransactions'])->get();

        return $csv->download($trainees, 'mcare-trainer-trainee-summary-'.now()->format('Y-m-d').'.csv');
    }

    public function sessions(Request $request, TrainingCalendarService $scheduleService): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $activeBatch = TrainingBatch::assignedTo($request->user());
        $month = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : $scheduleService->suggestedMonth($activeBatch);
        $sessions = $activeBatch ? $scheduleService->month($activeBatch, $month) : collect();

        return view('trainer.sessions', [
            'activeBatch' => $activeBatch,
            'month' => $month,
            'sessions' => $sessions,
            'sessionsByDate' => $sessions->groupBy('date_key'),
            'calendarSelectedDate' => $validated['date'] ?? null,
        ]);
    }

    public function resources(Request $request): View
    {
        $assignedBatch = TrainingBatch::assignedTo($request->user());

        $modules = TrainingModule::query()
            ->with([
                'batch',
                'targetTrainee',
                'progressRecords.application',
                'submodules',
                'quizzes.questions',
                'quizzes.attempts.application',
            ])
            ->where('trainer_id', $request->user()->id)
            ->latest('published_at')
            ->latest('id')
            ->get();

        $quizzes = Quiz::query()
            ->with(['trainingModule', 'batch', 'targetTrainee', 'questions', 'attempts.application'])
            ->where('trainer_id', $request->user()->id)
            ->latest('id')
            ->get();

        return view('trainer.resources', [
            'batches' => $assignedBatch ? collect([$assignedBatch]) : collect(),
            'modules' => $modules,
            'quizzes' => $quizzes,
            'trainees' => $this->approvedTrainees($assignedBatch)->with('batch')->get(),
            'assignedBatch' => $assignedBatch,
            'catalogUnits' => \App\Support\CaregivingNcIiCatalog::units(),
            'coreUnits' => \App\Support\CaregivingNcIiCatalog::coreUnits(),
        ]);
    }

    public function certificates(CompletionEligibilityService $eligibility): View
    {
        $assignedBatch = TrainingBatch::assignedTo(request()->user());
        $trainees = $this->approvedTrainees($assignedBatch)
            ->with(['batch', 'user'])
            ->orderBy('last_name')
            ->get();

        return view('trainer.certificates', [
            'trainees' => $trainees,
            'eligibilityByTrainee' => $trainees->mapWithKeys(
                fn (EnrollmentApplication $trainee): array => [
                    $trainee->id => $eligibility->evaluate($trainee),
                ],
            ),
        ]);
    }

    public function reports(): View
    {
        $activeBatch = TrainingBatch::assignedTo(request()->user());
        $trainees = $this->approvedTrainees($activeBatch)->get();

        return view('trainer.reports', [
            'activeBatch' => $activeBatch,
            'stats' => [
                'trainees' => $trainees->count(),
                'am' => $trainees->where('schedule_preference', 'AM')->count(),
                'pm' => $trainees->where('schedule_preference', 'PM')->count(),
                'modules' => TrainingModule::query()
                    ->where('is_published', true)
                    ->when($activeBatch, fn ($query) => $query->where('training_batch_id', $activeBatch->id))
                    ->when(! $activeBatch, fn ($query) => $query->whereRaw('1 = 0'))
                    ->count(),
                'paid' => $trainees->where('payment_status', EnrollmentApplication::PAYMENT_PAID)->count(),
                'module_completions' => ModuleProgress::query()
                    ->where('status', 'completed')
                    ->when($activeBatch, fn ($query) => $query->whereHas('application', fn ($nested) => $nested->where('training_batch_id', $activeBatch->id)))
                    ->when(! $activeBatch, fn ($query) => $query->whereRaw('1 = 0'))
                    ->count(),
            ],
            'assignedBatch' => $activeBatch,
        ]);
    }

    private function approvedTrainees(?TrainingBatch $batch = null)
    {
        return EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when($batch, fn ($query) => $query->where('training_batch_id', $batch->id))
            ->when(! $batch, fn ($query) => $query->whereRaw('1 = 0'));
    }

    private function traineeRosterQuery(
        string $search,
        ?int $batchId,
        ?string $schedule,
        ?TrainingBatch $assignedBatch,
    )
    {
        return $this->approvedTrainees($assignedBatch)
            ->when($batchId, fn ($query) => $query->where('training_batch_id', $batchId))
            ->when($schedule, fn ($query) => $query->where('schedule_preference', $schedule))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    private function assertBatchFilter(?TrainingBatch $assignedBatch, ?int $requestedBatchId): void
    {
        if ($requestedBatchId && (! $assignedBatch || $requestedBatchId !== (int) $assignedBatch->id)) {
            throw ValidationException::withMessages([
                'batch_id' => 'This trainer can only access the currently assigned batch.',
            ]);
        }
    }
}
